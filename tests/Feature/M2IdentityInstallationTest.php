<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Modules\Identity\Application\DTO\ChangeTemporaryPasswordData;
use App\Modules\Identity\Application\DTO\CreateIdentityData;
use App\Modules\Identity\Application\DTO\IdentityLifecycleData;
use App\Modules\Identity\Application\DTO\UpdateIdentityData;
use App\Modules\Identity\Application\Ports\In\CreateIdentity;
use App\Modules\Identity\Application\Ports\In\IdentityDirectory;
use App\Modules\Identity\Application\Ports\In\ManageIdentityCredentials;
use App\Modules\Identity\Application\Ports\In\ManageIdentityLifecycle;
use App\Modules\Identity\Domain\Exceptions\IdentityEmailAlreadyExists;
use App\Modules\Identity\Domain\Exceptions\PasswordPolicyViolation;
use App\Modules\Identity\Domain\Exceptions\ProtectedInstallationOwner;
use App\Modules\Installation\Application\DTO\InitializeInstallationData;
use App\Modules\Installation\Application\DTO\InstallationDetails;
use App\Modules\Installation\Application\DTO\RecoverOwnerData;
use App\Modules\Installation\Application\DTO\UpdateInstallationSettingsData;
use App\Modules\Installation\Application\Ports\In\InitializeInstallation;
use App\Modules\Installation\Application\Ports\In\InstallationSettings as InstallationSettingsContract;
use App\Modules\Installation\Application\Ports\In\RecoverInstallationOwner;
use App\Modules\Installation\Domain\Exceptions\InstallationOwnerRequired;
use App\Modules\Installation\Domain\ValueObjects\InstallationSettings;
use App\Shared\Application\Contracts\Clock;
use App\Shared\Application\Contracts\IntegrationEventPublisher;
use App\Shared\Application\Integration\IntegrationEventMessage;
use App\Shared\Domain\ValueObjects\CorrelationContext;
use App\Shared\Domain\ValueObjects\RequestId;
use App\Shared\Domain\ValueObjects\TraceId;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\FrozenClock;
use Tests\TestCase;

final class M2IdentityInstallationTest extends TestCase
{
    use RefreshDatabase;

    private FrozenClock $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clock = new FrozenClock(new DateTimeImmutable('2026-07-27T12:00:00+00:00'));
        $this->app->instance(Clock::class, $this->clock);
    }

    public function test_initialization_is_idempotent_and_creates_an_explicit_owner(): void
    {
        config()->set('app.name', 'Configured Product');

        $first = $this->initialize('OwnerTemporary!123');
        $second = $this->initialize('IgnoredTemporary!456');

        self::assertTrue($first->created);
        self::assertFalse($second->created);
        self::assertSame($first->id, $second->id);
        self::assertSame($first->ownerIdentityId, $second->ownerIdentityId);
        self::assertSame('pending_mfa', $first->state);
        self::assertSame('Configured Product', $first->settings['display_name']);
        self::assertSame(1, DB::table('installations')->count());
        self::assertSame(1, DB::table('identities')->count());
        self::assertSame(1, DB::table('identity_credentials')->count());
        self::assertSame(1, DB::table('audit_events')->where('action', 'installation.initialized')->count());

        $credential = DB::table('identity_credentials')->first();
        self::assertNotNull($credential);
        self::assertStringStartsWith('$argon2id$', (string) $credential->password_hash);
        self::assertStringNotContainsString('OwnerTemporary!123', (string) $credential->password_hash);
        self::assertSame(
            '2026-07-28 12:00:00.000000+00:00',
            (new DateTimeImmutable((string) $credential->temporary_expires_at))->format('Y-m-d H:i:s.uP'),
        );

        self::assertTrue($this->credentials()->verify('owner@example.test', 'OwnerTemporary!123')->valid);
        self::assertFalse($this->credentials()->verify('owner@example.test', 'IgnoredTemporary!456')->valid);
    }

    public function test_email_normalization_prevents_duplicate_active_identities(): void
    {
        $this->initialize();

        $this->expectException(IdentityEmailAlreadyExists::class);

        $this->createIdentity()->create(new CreateIdentityData(
            email: ' OWNER@EXAMPLE.TEST ',
            displayName: 'Duplicate Owner',
            temporaryPassword: 'AnotherTemporary!123',
            actor: AuditActor::system(),
            correlation: $this->correlation(),
        ));
    }

    public function test_temporary_password_lifetime_is_bounded_to_seventy_two_hours(): void
    {
        $identity = $this->createIdentity()->create(new CreateIdentityData(
            email: 'person@example.test',
            displayName: 'Person',
            temporaryPassword: 'TemporaryPassword!123',
            actor: AuditActor::system(),
            correlation: $this->correlation(),
            temporaryPasswordHours: 72,
        ));

        $credential = DB::table('identity_credentials')->where('identity_id', $identity->id)->first();
        self::assertNotNull($credential);
        self::assertSame(
            '2026-07-30T12:00:00+00:00',
            (new DateTimeImmutable((string) $credential->temporary_expires_at))->format(DATE_ATOM),
        );

        try {
            $this->createIdentity()->create(new CreateIdentityData(
                email: 'other@example.test',
                displayName: 'Other',
                temporaryPassword: 'TemporaryPassword!456',
                actor: AuditActor::system(),
                correlation: $this->correlation(),
                temporaryPasswordHours: 73,
            ));
            self::fail('A lifetime above 72 hours must be rejected.');
        } catch (PasswordPolicyViolation) {
            self::assertDatabaseMissing('identities', ['email_normalized' => 'other@example.test']);
        }
    }

    public function test_temporary_password_must_be_changed_and_expires(): void
    {
        $installation = $this->initialize();
        $identityId = UuidV7::fromString($installation->ownerIdentityId);

        $verification = $this->credentials()->verify('owner@example.test', 'OwnerTemporary!123');
        self::assertTrue($verification->valid);
        self::assertTrue($verification->mustChangePassword);

        $changed = $this->credentials()->changeTemporaryPassword(new ChangeTemporaryPasswordData(
            identityId: $identityId,
            temporaryPassword: 'OwnerTemporary!123',
            newPassword: 'PermanentPassword!456',
            correlation: $this->correlation(),
        ));

        self::assertFalse($changed->mustChangePassword);
        self::assertFalse($this->credentials()->verify('owner@example.test', 'OwnerTemporary!123')->valid);
        self::assertTrue($this->credentials()->verify('owner@example.test', 'PermanentPassword!456')->valid);
        self::assertNull(DB::table('identity_credentials')->value('temporary_expires_at'));

        $expiring = $this->createIdentity()->create(new CreateIdentityData(
            email: 'expiring@example.test',
            displayName: 'Expiring',
            temporaryPassword: 'ExpiringPassword!123',
            actor: AuditActor::system(),
            correlation: $this->correlation(),
            temporaryPasswordHours: 1,
        ));
        self::assertNotEmpty($expiring->id);

        $this->clock->set(new DateTimeImmutable('2026-07-27T13:00:01+00:00'));
        self::assertFalse($this->credentials()->verify('expiring@example.test', 'ExpiringPassword!123')->valid);
    }

    public function test_owner_is_protected_and_other_identity_lifecycle_is_audited(): void
    {
        $installation = $this->initialize();
        $ownerId = UuidV7::fromString($installation->ownerIdentityId);

        try {
            $this->lifecycle()->deactivate(new IdentityLifecycleData(
                identityId: $ownerId,
                actor: AuditActor::system(),
                correlation: $this->correlation(),
            ));
            self::fail('Owner deactivation must be rejected.');
        } catch (ProtectedInstallationOwner) {
            self::assertDatabaseHas('identities', ['id' => $ownerId->toString(), 'status' => 'active']);
        }

        $person = $this->createIdentity()->create(new CreateIdentityData(
            email: 'person@example.test',
            displayName: 'Person',
            temporaryPassword: 'PersonTemporary!123',
            actor: AuditActor::identity($ownerId),
            correlation: $this->correlation(),
        ));
        $personId = UuidV7::fromString($person->id);
        $actor = AuditActor::identity($ownerId);

        $this->lifecycle()->deactivate(new IdentityLifecycleData($personId, $actor, $this->correlation(), 'security_hold'));
        self::assertFalse($this->credentials()->verify('person@example.test', 'PersonTemporary!123')->valid);
        $this->lifecycle()->reactivate(new IdentityLifecycleData($personId, $actor, $this->correlation()));
        $this->lifecycle()->softDelete(new IdentityLifecycleData($personId, $actor, $this->correlation(), 'requested_removal'));
        $restored = $this->lifecycle()->restore(new IdentityLifecycleData($personId, $actor, $this->correlation()));

        self::assertSame('disabled', $restored->status);
        self::assertNull($restored->deletedAt);
        self::assertFalse($this->credentials()->verify('person@example.test', 'PersonTemporary!123')->valid);
        self::assertSame(
            4,
            DB::table('audit_events')->whereIn('action', [
                'identity.deactivate',
                'identity.reactivate',
                'identity.soft_delete',
                'identity.restore',
            ])->count(),
        );
        self::assertSame(
            4,
            DB::table('outbox_messages')->where('event_name', 'identity.lifecycle.changed.v1')->count(),
        );
    }

    public function test_restore_rejects_an_email_now_used_by_another_active_identity(): void
    {
        $first = $this->createIdentity()->create(new CreateIdentityData(
            email: 'shared@example.test',
            displayName: 'First',
            temporaryPassword: 'FirstTemporary!123',
            actor: AuditActor::system(),
            correlation: $this->correlation(),
        ));
        $firstId = UuidV7::fromString($first->id);
        $this->lifecycle()->softDelete(new IdentityLifecycleData(
            $firstId,
            AuditActor::system(),
            $this->correlation(),
        ));
        $this->createIdentity()->create(new CreateIdentityData(
            email: 'SHARED@example.test',
            displayName: 'Second',
            temporaryPassword: 'SecondTemporary!123',
            actor: AuditActor::system(),
            correlation: $this->correlation(),
        ));

        $this->expectException(IdentityEmailAlreadyExists::class);

        $this->lifecycle()->restore(new IdentityLifecycleData(
            $firstId,
            AuditActor::system(),
            $this->correlation(),
        ));
    }

    public function test_profile_update_respects_active_email_uniqueness(): void
    {
        $first = $this->createIdentity()->create(new CreateIdentityData(
            email: 'first@example.test',
            displayName: 'First',
            temporaryPassword: 'FirstTemporary!123',
            actor: AuditActor::system(),
            correlation: $this->correlation(),
        ));
        $this->createIdentity()->create(new CreateIdentityData(
            email: 'second@example.test',
            displayName: 'Second',
            temporaryPassword: 'SecondTemporary!123',
            actor: AuditActor::system(),
            correlation: $this->correlation(),
        ));

        $this->expectException(IdentityEmailAlreadyExists::class);

        $this->directory()->update(new UpdateIdentityData(
            identityId: UuidV7::fromString($first->id),
            email: 'SECOND@example.test',
            displayName: 'First Renamed',
            actor: AuditActor::system(),
            correlation: $this->correlation(),
        ));
    }

    public function test_only_owner_updates_public_installation_settings(): void
    {
        $installation = $this->initialize();
        $owner = AuditActor::identity(UuidV7::fromString($installation->ownerIdentityId));
        $settings = new InstallationSettings(
            displayName: 'New Product',
            shortName: 'Product',
            legalName: 'New Product Ltd.',
            institutionalDescription: 'Identity for the product ecosystem.',
            logoUrl: 'https://example.test/logo.svg',
            logoDarkUrl: null,
            faviconUrl: 'https://example.test/favicon.ico',
            primaryColor: '#123456',
            secondaryColor: '#ABCDEF',
            accentColor: '#FEDCBA',
            locale: 'pt_BR',
            timezone: 'America/Sao_Paulo',
            senderName: 'Product Notifications',
            senderEmail: 'notifications@example.test',
            supportEmail: 'support@example.test',
            supportUrl: 'https://example.test/support',
            termsUrl: 'https://example.test/terms',
            privacyPolicyUrl: 'https://example.test/privacy',
        );

        $updated = $this->installationSettings()->update(new UpdateInstallationSettingsData(
            settings: $settings,
            actor: $owner,
            correlation: $this->correlation(),
        ));

        self::assertSame('New Product', $updated->settings['display_name']);
        self::assertDatabaseHas('installations', [
            'display_name' => 'New Product',
            'primary_color' => '#123456',
            'accent_color' => '#FEDCBA',
            'sender_email' => 'notifications@example.test',
            'terms_url' => 'https://example.test/terms',
        ]);
        self::assertDatabaseHas('audit_events', ['action' => 'installation.settings_updated']);

        $this->expectException(InstallationOwnerRequired::class);
        $this->installationSettings()->update(new UpdateInstallationSettingsData(
            settings: $settings,
            actor: AuditActor::system(),
            correlation: $this->correlation(),
        ));
    }

    public function test_recovery_resets_owner_credential_without_exposing_password(): void
    {
        $this->initialize();
        $newPassword = 'RecoveredTemporary!789';

        $owner = $this->recovery()->recover(new RecoverOwnerData(
            temporaryPassword: $newPassword,
            correlation: $this->correlation(),
        ));

        self::assertTrue($owner->mustChangePassword);
        self::assertFalse($this->credentials()->verify('owner@example.test', 'OwnerTemporary!123')->valid);
        self::assertTrue($this->credentials()->verify('owner@example.test', $newPassword)->valid);
        self::assertDatabaseHas('audit_events', ['action' => 'installation.owner_recovered']);
        self::assertDatabaseHas('audit_events', ['action' => 'identity.temporary_password_reset']);

        $serializedAudit = DB::table('audit_events')
            ->get(['before_values', 'after_values', 'metadata'])
            ->map(fn (object $row): string => json_encode($row, JSON_THROW_ON_ERROR))
            ->implode("\n");
        self::assertStringNotContainsString($newPassword, $serializedAudit);
        self::assertStringNotContainsString($newPassword, (string) DB::table('outbox_messages')->pluck('payload')->implode("\n"));
    }

    public function test_protected_tables_reject_hard_delete(): void
    {
        $installation = $this->initialize();

        $this->assertHardDeleteRejected('installations', $installation->id);
        $this->assertHardDeleteRejected('identities', $installation->ownerIdentityId);
    }

    public function test_state_audit_and_outbox_roll_back_together(): void
    {
        $this->app->instance(IntegrationEventPublisher::class, new class implements IntegrationEventPublisher
        {
            public function publish(IntegrationEventMessage $event): void
            {
                throw new RuntimeException('Outbox unavailable.');
            }
        });

        $this->expectException(RuntimeException::class);

        try {
            $this->createIdentity()->create(new CreateIdentityData(
                email: 'rollback@example.test',
                displayName: 'Rollback',
                temporaryPassword: 'RollbackTemporary!123',
                actor: AuditActor::system(),
                correlation: $this->correlation(),
            ));
        } finally {
            self::assertSame(0, DB::table('identities')->count());
            self::assertSame(0, DB::table('identity_credentials')->count());
            self::assertSame(0, DB::table('audit_events')->count());
            self::assertSame(0, DB::table('outbox_messages')->count());
        }
    }

    public function test_recovery_command_requires_explicit_confirmation(): void
    {
        $this->artisan('installation:recover-owner')
            ->expectsOutputToContain('--confirm-owner-recovery')
            ->assertExitCode(Command::FAILURE);
    }

    private function initialize(string $password = 'OwnerTemporary!123'): InstallationDetails
    {
        return $this->app->make(InitializeInstallation::class)->initialize(new InitializeInstallationData(
            ownerEmail: 'owner@example.test',
            ownerDisplayName: 'Installation Owner',
            temporaryPassword: $password,
            correlation: $this->correlation(),
        ));
    }

    private function createIdentity(): CreateIdentity
    {
        return $this->app->make(CreateIdentity::class);
    }

    private function lifecycle(): ManageIdentityLifecycle
    {
        return $this->app->make(ManageIdentityLifecycle::class);
    }

    private function credentials(): ManageIdentityCredentials
    {
        return $this->app->make(ManageIdentityCredentials::class);
    }

    private function assertHardDeleteRejected(string $table, string $id): void
    {
        DB::statement('SAVEPOINT hard_delete_guard');

        try {
            DB::table($table)->where('id', $id)->delete();
            DB::statement('RELEASE SAVEPOINT hard_delete_guard');

            self::fail("{$table} hard delete must fail.");
        } catch (QueryException) {
            DB::statement('ROLLBACK TO SAVEPOINT hard_delete_guard');
            DB::statement('RELEASE SAVEPOINT hard_delete_guard');
        }

        self::assertDatabaseHas($table, ['id' => $id]);
    }

    private function directory(): IdentityDirectory
    {
        return $this->app->make(IdentityDirectory::class);
    }

    private function installationSettings(): InstallationSettingsContract
    {
        return $this->app->make(InstallationSettingsContract::class);
    }

    private function recovery(): RecoverInstallationOwner
    {
        return $this->app->make(RecoverInstallationOwner::class);
    }

    private function correlation(): CorrelationContext
    {
        return new CorrelationContext(
            requestId: RequestId::fromString('018f47a2-4b9d-7cc1-8b7a-112233445566'),
            traceId: TraceId::fromString('4bf92f3577b34da6a3ce929d0e0e4736'),
        );
    }
}
