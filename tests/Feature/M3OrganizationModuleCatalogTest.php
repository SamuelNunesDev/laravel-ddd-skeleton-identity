<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Modules\Identity\Application\DTO\CreateIdentityData;
use App\Modules\Identity\Application\DTO\IdentityLifecycleData;
use App\Modules\Identity\Application\Ports\In\CreateIdentity;
use App\Modules\Identity\Application\Ports\In\ManageIdentityLifecycle;
use App\Modules\ModuleCatalog\Application\DTO\ChangeOrganizationModuleData;
use App\Modules\ModuleCatalog\Application\DTO\ModuleDetails;
use App\Modules\ModuleCatalog\Application\DTO\ModuleLifecycleData;
use App\Modules\ModuleCatalog\Application\DTO\OrganizationModuleDetails;
use App\Modules\ModuleCatalog\Application\DTO\RegisterModuleData;
use App\Modules\ModuleCatalog\Application\DTO\UpdateModuleData;
use App\Modules\ModuleCatalog\Application\Ports\In\ManageModules;
use App\Modules\ModuleCatalog\Application\Ports\In\ManageOrganizationModules;
use App\Modules\ModuleCatalog\Domain\Exceptions\ModuleIdentifierAlreadyExists;
use App\Modules\ModuleCatalog\Domain\Exceptions\ModuleUnavailable;
use App\Modules\ModuleCatalog\Domain\Exceptions\OrganizationModuleAlreadyEnabled;
use App\Modules\ModuleCatalog\Domain\Exceptions\OrganizationModuleNotEnabled;
use App\Modules\Organization\Application\DTO\CreateMembershipData;
use App\Modules\Organization\Application\DTO\CreateOrganizationData;
use App\Modules\Organization\Application\DTO\EndMembershipData;
use App\Modules\Organization\Application\DTO\MembershipDetails;
use App\Modules\Organization\Application\DTO\OrganizationDetails;
use App\Modules\Organization\Application\DTO\OrganizationLifecycleData;
use App\Modules\Organization\Application\DTO\ResolveOrganizationContextData;
use App\Modules\Organization\Application\DTO\UpdateOrganizationData;
use App\Modules\Organization\Application\Ports\In\ManageMemberships;
use App\Modules\Organization\Application\Ports\In\ManageOrganizations;
use App\Modules\Organization\Application\Ports\In\OrganizationSelection;
use App\Modules\Organization\Application\Ports\In\ResolveOrganizationContext;
use App\Modules\Organization\Domain\Exceptions\InvalidOrganizationContext;
use App\Modules\Organization\Domain\Exceptions\MembershipAlreadyActive;
use App\Modules\Organization\Domain\Exceptions\OrganizationIdentifierAlreadyExists;
use App\Modules\Organization\Domain\ValueObjects\MfaPolicy;
use App\Modules\Organization\Domain\ValueObjects\OrganizationContext;
use App\Modules\Organization\Domain\ValueObjects\OrganizationContextSource;
use App\Shared\Application\Contracts\Clock;
use App\Shared\Application\Contracts\IntegrationEventPublisher;
use App\Shared\Application\Integration\IntegrationEventMessage;
use App\Shared\Domain\ValueObjects\CorrelationContext;
use App\Shared\Domain\ValueObjects\RequestId;
use App\Shared\Domain\ValueObjects\TraceId;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\FrozenClock;
use Tests\TestCase;

final class M3OrganizationModuleCatalogTest extends TestCase
{
    use RefreshDatabase;

    private FrozenClock $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clock = new FrozenClock(new DateTimeImmutable('2026-07-27T12:00:00+00:00'));
        $this->app->instance(Clock::class, $this->clock);
    }

    public function test_organization_lifecycle_mfa_policy_and_identifier_are_audited(): void
    {
        $actorId = $this->createIdentity('owner@example.test');
        $actor = AuditActor::identity($actorId);
        $organization = $this->createOrganization('acme', 'Acme', $actor, MfaPolicy::Optional);
        $organizationId = UuidV7::fromString($organization->id);

        $updated = $this->organizations()->update(new UpdateOrganizationData(
            organizationId: $organizationId,
            name: 'Acme Incorporated',
            mfaPolicy: MfaPolicy::Required,
            actor: $actor,
            correlation: $this->correlation(),
        ));
        self::assertSame('required', $updated->mfaPolicy);

        try {
            $this->createOrganization('ACME', 'Duplicate Acme', $actor);
            self::fail('Organization identifier must remain globally unique.');
        } catch (OrganizationIdentifierAlreadyExists) {
            self::assertSame(1, DB::table('organizations')->where('identifier', 'acme')->count());
        }

        $this->organizations()->deactivate($this->organizationLifecycle($organizationId, $actor));

        try {
            $this->memberships()->add(new CreateMembershipData(
                identityId: $this->createIdentity('blocked@example.test'),
                organizationId: $organizationId,
                actor: $actor,
                correlation: $this->correlation(),
            ));
            self::fail('A disabled organization cannot accept a new membership.');
        } catch (InvalidOrganizationContext) {
            self::assertSame(0, DB::table('memberships')->where('organization_id', $organization->id)->count());
        }

        $this->organizations()->reactivate($this->organizationLifecycle($organizationId, $actor));
        $this->organizations()->softDelete($this->organizationLifecycle($organizationId, $actor));
        $restored = $this->organizations()->restore($this->organizationLifecycle($organizationId, $actor));

        self::assertSame('disabled', $restored->status);
        self::assertNull($restored->deletedAt);
        self::assertSame(6, DB::table('audit_events')->where('organization_id', $organization->id)->count());
        self::assertSame(6, DB::table('outbox_messages')->where('aggregate_type', 'organization')->count());
    }

    public function test_membership_end_preserves_history_and_cross_organization_end_is_rejected(): void
    {
        $actorId = $this->createIdentity('owner@example.test');
        $memberId = $this->createIdentity('member@example.test');
        $actor = AuditActor::identity($actorId);
        $first = $this->createOrganization('first', 'First', $actor);
        $second = $this->createOrganization('second', 'Second', $actor);
        $firstId = UuidV7::fromString($first->id);
        $secondId = UuidV7::fromString($second->id);
        $this->addMembership($actorId, $firstId, $actor);
        $this->addMembership($actorId, $secondId, $actor);
        $membership = $this->memberships()->add(new CreateMembershipData(
            identityId: $memberId,
            organizationId: $firstId,
            actor: $actor,
            correlation: $this->correlation(),
        ));

        try {
            $this->memberships()->add(new CreateMembershipData(
                identityId: $memberId,
                organizationId: $firstId,
                actor: $actor,
                correlation: $this->correlation(),
            ));
            self::fail('Only one active membership may exist.');
        } catch (MembershipAlreadyActive) {
            self::assertSame(1, DB::table('memberships')
                ->where('identity_id', $memberId->toString())
                ->where('organization_id', $first->id)
                ->where('status', 'active')
                ->count());
        }

        try {
            $this->memberships()->end(new EndMembershipData(
                membershipId: UuidV7::fromString($membership->id),
                organizationId: $secondId,
                actor: $actor,
                correlation: $this->correlation(),
            ));
            self::fail('A membership cannot be mutated through another organization.');
        } catch (InvalidOrganizationContext) {
            self::assertDatabaseHas('memberships', ['id' => $membership->id, 'status' => 'active']);
        }

        $this->memberships()->end(new EndMembershipData(
            membershipId: UuidV7::fromString($membership->id),
            organizationId: $firstId,
            actor: $actor,
            correlation: $this->correlation(),
            reason: 'contract_ended',
        ));
        $firstHistory = $this->memberships()->forOrganization($this->context($actorId, $firstId));
        $secondHistory = $this->memberships()->forOrganization($this->context($actorId, $secondId));
        self::assertContains($membership->id, array_map(static fn ($item): string => $item->id, $firstHistory));
        self::assertNotContains($membership->id, array_map(static fn ($item): string => $item->id, $secondHistory));

        $this->expectException(InvalidOrganizationContext::class);
        $this->context($memberId, $firstId);
    }

    public function test_ended_membership_allows_new_period_without_losing_history(): void
    {
        $actorId = $this->createIdentity('owner@example.test');
        $memberId = $this->createIdentity('member@example.test');
        $actor = AuditActor::identity($actorId);
        $organization = $this->createOrganization('acme', 'Acme', $actor);
        $organizationId = UuidV7::fromString($organization->id);
        $first = $this->memberships()->add(new CreateMembershipData(
            $memberId,
            $organizationId,
            $actor,
            $this->correlation(),
        ));
        $this->memberships()->end(new EndMembershipData(
            UuidV7::fromString($first->id),
            $organizationId,
            $actor,
            $this->correlation(),
        ));

        $this->clock->set(new DateTimeImmutable('2026-07-28T12:00:00+00:00'));
        $second = $this->memberships()->add(new CreateMembershipData(
            $memberId,
            $organizationId,
            $actor,
            $this->correlation(),
        ));

        self::assertNotSame($first->id, $second->id);
        self::assertSame(2, DB::table('memberships')->where('identity_id', $memberId->toString())->count());
        self::assertSame(1, DB::table('memberships')->where('status', 'active')->count());
        self::assertSame($organization->id, $this->context($memberId, $organizationId)->organizationId->toString());
    }

    public function test_module_metadata_lifecycle_and_identifier_are_persistent_and_audited(): void
    {
        $actorId = $this->createIdentity('owner@example.test');
        $actor = AuditActor::identity($actorId);
        $module = $this->registerModule('sales', $actor, ['sales-api', 'sales-api'], ['profile', 'sales.orders.read']);
        $moduleId = UuidV7::fromString($module->id);

        self::assertSame(['sales-api'], $module->audiences);
        self::assertSame(['profile', 'sales.orders.read'], $module->allowedScopes);

        $updated = $this->modules()->update(new UpdateModuleData(
            moduleId: $moduleId,
            name: 'Sales Platform',
            description: 'Sales and order management.',
            audiences: ['sales-v2-api'],
            allowedScopes: ['sales.orders.write'],
            actor: $actor,
            correlation: $this->correlation(),
        ));
        self::assertSame(['sales-v2-api'], $updated->audiences);
        self::assertSame(2, DB::table('module_audiences')->count());
        self::assertDatabaseHas('module_audiences', ['audience' => 'sales-api', 'active' => false]);

        try {
            $this->registerModule('SALES', $actor);
            self::fail('Module identifier must remain globally unique.');
        } catch (ModuleIdentifierAlreadyExists) {
            self::assertSame(1, DB::table('modules')->where('identifier', 'sales')->count());
        }

        $this->modules()->deactivate($this->moduleLifecycle($moduleId, $actor));
        $this->modules()->reactivate($this->moduleLifecycle($moduleId, $actor));
        $this->modules()->softDelete($this->moduleLifecycle($moduleId, $actor));
        $restored = $this->modules()->restore($this->moduleLifecycle($moduleId, $actor));

        self::assertSame('disabled', $restored->status);
        self::assertSame(6, DB::table('audit_events')->where('module_id', $module->id)->count());
    }

    public function test_enablement_and_selection_are_isolated_by_organization(): void
    {
        $identityId = $this->createIdentity('owner@example.test');
        $actor = AuditActor::identity($identityId);
        $first = $this->createOrganization('first', 'First', $actor);
        $second = $this->createOrganization('second', 'Second', $actor);
        $firstId = UuidV7::fromString($first->id);
        $secondId = UuidV7::fromString($second->id);
        $this->addMembership($identityId, $firstId, $actor);
        $this->addMembership($identityId, $secondId, $actor);
        $sales = $this->registerModule('sales', $actor);
        $finance = $this->registerModule('finance', $actor);
        $salesId = UuidV7::fromString($sales->id);
        $financeId = UuidV7::fromString($finance->id);
        $firstContext = $this->context($identityId, $firstId);
        $secondContext = $this->context($identityId, $secondId);

        $this->enable($firstContext, $salesId);
        $this->enable($firstContext, $financeId);
        $this->enable($secondContext, $financeId);

        self::assertSame(['first'], array_map(
            static fn ($option): string => $option->identifier,
            $this->selection()->organizationsFor($identityId, $salesId),
        ));
        self::assertSame(['finance', 'sales'], array_map(
            static fn ($option): string => $option->identifier,
            $this->selection()->modulesFor($firstContext),
        ));
        self::assertSame(['sales'], array_map(
            static fn ($option): string => $option->identifier,
            $this->selection()->modulesFor($this->context($identityId, $firstId, $salesId)),
        ));
        self::assertSame([$first->id], $this->organizationModules()->enabledOrganizationIdsFor($salesId));

        try {
            $this->organizationModules()->disable(new ChangeOrganizationModuleData(
                context: $secondContext,
                moduleId: $salesId,
                correlation: $this->correlation(),
            ));
            self::fail('Another organization context cannot disable this enablement.');
        } catch (OrganizationModuleNotEnabled) {
            self::assertDatabaseHas('organization_modules', [
                'organization_id' => $first->id,
                'module_id' => $sales->id,
                'status' => 'enabled',
            ]);
        }

        $this->expectException(InvalidOrganizationContext::class);
        $this->context($identityId, $secondId, $salesId);
    }

    public function test_disabling_enablement_or_module_removes_structural_applicability(): void
    {
        $identityId = $this->createIdentity('owner@example.test');
        $actor = AuditActor::identity($identityId);
        $organization = $this->createOrganization('acme', 'Acme', $actor);
        $organizationId = UuidV7::fromString($organization->id);
        $this->addMembership($identityId, $organizationId, $actor);
        $module = $this->registerModule('sales', $actor);
        $moduleId = UuidV7::fromString($module->id);
        $context = $this->context($identityId, $organizationId);
        $this->enable($context, $moduleId);

        try {
            $this->enable($context, $moduleId);
            self::fail('Only one active enablement may exist for an organization and module.');
        } catch (OrganizationModuleAlreadyEnabled) {
            self::assertSame(1, DB::table('organization_modules')->where('status', 'enabled')->count());
        }

        $this->organizationModules()->disable(new ChangeOrganizationModuleData(
            context: $context,
            moduleId: $moduleId,
            correlation: $this->correlation(),
        ));
        self::assertSame([], $this->selection()->organizationsFor($identityId, $moduleId));
        self::assertSame(1, DB::table('organization_modules')->where('status', 'disabled')->count());

        $this->enable($context, $moduleId);
        self::assertSame(2, DB::table('organization_modules')->count());
        $this->modules()->deactivate($this->moduleLifecycle($moduleId, $actor));
        self::assertSame([], $this->selection()->organizationsFor($identityId, $moduleId));
        self::assertSame([], $this->organizationModules()->enabledOrganizationIdsFor($moduleId));
    }

    public function test_disabled_module_blocks_new_enablement(): void
    {
        $identityId = $this->createIdentity('owner@example.test');
        $actor = AuditActor::identity($identityId);
        $organization = $this->createOrganization('acme', 'Acme', $actor);
        $organizationId = UuidV7::fromString($organization->id);
        $this->addMembership($identityId, $organizationId, $actor);
        $module = $this->registerModule('sales', $actor);
        $moduleId = UuidV7::fromString($module->id);
        $this->modules()->deactivate($this->moduleLifecycle($moduleId, $actor));

        $this->expectException(ModuleUnavailable::class);

        $this->enable($this->context($identityId, $organizationId), $moduleId);
    }

    public function test_preference_is_central_and_never_forces_an_invalid_context(): void
    {
        $identityId = $this->createIdentity('owner@example.test');
        $actor = AuditActor::identity($identityId);
        $first = $this->createOrganization('first', 'First', $actor);
        $second = $this->createOrganization('second', 'Second', $actor);
        $firstId = UuidV7::fromString($first->id);
        $secondId = UuidV7::fromString($second->id);
        $firstMembership = $this->addMembership($identityId, $firstId, $actor);
        $this->addMembership($identityId, $secondId, $actor);
        $firstContext = $this->context($identityId, $firstId);
        $this->selection()->remember($firstContext, $this->correlation());

        self::assertSame(
            $first->id,
            $this->selection()->preferred($identityId, null, OrganizationContextSource::Session)?->organizationId->toString(),
        );
        self::assertSame(1, DB::table('identity_preferences')->where('identity_id', $identityId->toString())->count());

        $this->memberships()->end(new EndMembershipData(
            membershipId: UuidV7::fromString($firstMembership->id),
            organizationId: $firstId,
            actor: $actor,
            correlation: $this->correlation(),
        ));

        self::assertNull(
            $this->selection()->preferred($identityId, null, OrganizationContextSource::Session),
        );
        self::assertSame(['second'], array_map(
            static fn ($option): string => $option->identifier,
            $this->selection()->organizationsFor($identityId),
        ));

        $this->selection()->remember($this->context($identityId, $secondId), $this->correlation());
        self::assertDatabaseHas('identity_preferences', [
            'identity_id' => $identityId->toString(),
            'last_organization_id' => $second->id,
        ]);
    }

    public function test_stale_authorization_version_invalidates_an_existing_context(): void
    {
        $identityId = $this->createIdentity('owner@example.test');
        $actor = AuditActor::identity($identityId);
        $organization = $this->createOrganization('acme', 'Acme', $actor);
        $organizationId = UuidV7::fromString($organization->id);
        $this->addMembership($identityId, $organizationId, $actor);
        $module = $this->registerModule('sales', $actor);
        $context = $this->context($identityId, $organizationId);
        $this->enable($context, UuidV7::fromString($module->id));

        $this->app->make(ManageIdentityLifecycle::class)->deactivate(new IdentityLifecycleData(
            identityId: $identityId,
            actor: $actor,
            correlation: $this->correlation(),
        ));
        $this->app->make(ManageIdentityLifecycle::class)->reactivate(new IdentityLifecycleData(
            identityId: $identityId,
            actor: $actor,
            correlation: $this->correlation(),
        ));

        $this->expectException(InvalidOrganizationContext::class);

        $this->selection()->modulesFor($context);
    }

    public function test_protected_m3_tables_reject_hard_delete(): void
    {
        $identityId = $this->createIdentity('owner@example.test');
        $actor = AuditActor::identity($identityId);
        $organization = $this->createOrganization('acme', 'Acme', $actor);
        $organizationId = UuidV7::fromString($organization->id);
        $membership = $this->addMembership($identityId, $organizationId, $actor);
        $module = $this->registerModule('sales', $actor);
        $moduleId = UuidV7::fromString($module->id);
        $enablement = $this->enable($this->context($identityId, $organizationId), $moduleId);

        $this->assertHardDeleteRejected('organization_modules', $enablement->id);
        $this->assertHardDeleteRejected('memberships', $membership->id);
        $this->assertHardDeleteRejected('modules', $module->id);
        $this->assertHardDeleteRejected('organizations', $organization->id);
    }

    public function test_organization_state_audit_and_outbox_roll_back_together(): void
    {
        $actorId = $this->createIdentity('owner@example.test');
        $this->app->instance(IntegrationEventPublisher::class, new class implements IntegrationEventPublisher
        {
            public function publish(IntegrationEventMessage $event): void
            {
                throw new RuntimeException('Outbox unavailable.');
            }
        });

        try {
            $this->createOrganization('rollback', 'Rollback', AuditActor::identity($actorId));
            self::fail('The failed outbox write must abort the operation.');
        } catch (RuntimeException) {
            self::assertDatabaseMissing('organizations', ['identifier' => 'rollback']);
            self::assertDatabaseMissing('audit_events', ['action' => 'organization.created']);
        }
    }

    private function createIdentity(string $email): UuidV7
    {
        $identity = $this->app->make(CreateIdentity::class)->create(new CreateIdentityData(
            email: $email,
            displayName: ucfirst(strstr($email, '@', true) ?: 'Person'),
            temporaryPassword: 'TemporaryPassword!123',
            actor: AuditActor::system(),
            correlation: $this->correlation(),
        ));

        return UuidV7::fromString($identity->id);
    }

    private function createOrganization(
        string $identifier,
        string $name,
        AuditActor $actor,
        MfaPolicy $mfaPolicy = MfaPolicy::Optional,
    ): OrganizationDetails {
        return $this->organizations()->create(new CreateOrganizationData(
            identifier: $identifier,
            name: $name,
            mfaPolicy: $mfaPolicy,
            actor: $actor,
            correlation: $this->correlation(),
        ));
    }

    private function addMembership(
        UuidV7 $identityId,
        UuidV7 $organizationId,
        AuditActor $actor,
    ): MembershipDetails {
        return $this->memberships()->add(new CreateMembershipData(
            identityId: $identityId,
            organizationId: $organizationId,
            actor: $actor,
            correlation: $this->correlation(),
        ));
    }

    private function registerModule(
        string $identifier,
        AuditActor $actor,
        array $audiences = ['default-api'],
        array $allowedScopes = ['openid'],
    ): ModuleDetails {
        return $this->modules()->register(new RegisterModuleData(
            identifier: $identifier,
            name: ucfirst($identifier),
            description: ucfirst($identifier).' module.',
            audiences: $audiences,
            allowedScopes: $allowedScopes,
            actor: $actor,
            correlation: $this->correlation(),
        ));
    }

    private function enable(
        OrganizationContext $context,
        UuidV7 $moduleId,
    ): OrganizationModuleDetails {
        return $this->organizationModules()->enable(new ChangeOrganizationModuleData(
            context: $context,
            moduleId: $moduleId,
            correlation: $this->correlation(),
        ));
    }

    private function context(
        UuidV7 $identityId,
        UuidV7 $organizationId,
        ?UuidV7 $moduleId = null,
    ): OrganizationContext {
        return $this->contextResolver()->resolve(new ResolveOrganizationContextData(
            identityId: $identityId,
            organizationId: $organizationId,
            moduleId: $moduleId,
            source: OrganizationContextSource::Session,
        ));
    }

    private function organizationLifecycle(
        UuidV7 $organizationId,
        AuditActor $actor,
    ): OrganizationLifecycleData {
        return new OrganizationLifecycleData(
            organizationId: $organizationId,
            actor: $actor,
            correlation: $this->correlation(),
        );
    }

    private function moduleLifecycle(UuidV7 $moduleId, AuditActor $actor): ModuleLifecycleData
    {
        return new ModuleLifecycleData(
            moduleId: $moduleId,
            actor: $actor,
            correlation: $this->correlation(),
        );
    }

    private function correlation(): CorrelationContext
    {
        return new CorrelationContext(
            requestId: new RequestId(UuidV7::fromString('019fa000-0000-7000-8000-000000000100')),
            traceId: TraceId::fromString('1234567890abcdef1234567890abcdef'),
        );
    }

    private function organizations(): ManageOrganizations
    {
        return $this->app->make(ManageOrganizations::class);
    }

    private function memberships(): ManageMemberships
    {
        return $this->app->make(ManageMemberships::class);
    }

    private function contextResolver(): ResolveOrganizationContext
    {
        return $this->app->make(ResolveOrganizationContext::class);
    }

    private function selection(): OrganizationSelection
    {
        return $this->app->make(OrganizationSelection::class);
    }

    private function modules(): ManageModules
    {
        return $this->app->make(ManageModules::class);
    }

    private function organizationModules(): ManageOrganizationModules
    {
        return $this->app->make(ManageOrganizationModules::class);
    }

    private function assertHardDeleteRejected(string $table, string $id): void
    {
        DB::statement('SAVEPOINT m3_hard_delete_guard');

        try {
            DB::table($table)->where('id', $id)->delete();
            DB::statement('RELEASE SAVEPOINT m3_hard_delete_guard');

            self::fail("{$table} hard delete must fail.");
        } catch (QueryException) {
            DB::statement('ROLLBACK TO SAVEPOINT m3_hard_delete_guard');
            DB::statement('RELEASE SAVEPOINT m3_hard_delete_guard');
        }

        self::assertDatabaseHas($table, ['id' => $id]);
    }
}
