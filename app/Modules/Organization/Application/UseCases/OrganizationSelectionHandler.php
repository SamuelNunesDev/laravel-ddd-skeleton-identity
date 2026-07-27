<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\UseCases;

use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Modules\Organization\Application\DTO\AvailableModule;
use App\Modules\Organization\Application\DTO\OrganizationOption;
use App\Modules\Organization\Application\DTO\ResolveOrganizationContextData;
use App\Modules\Organization\Application\Ports\In\OrganizationSelection;
use App\Modules\Organization\Application\Ports\In\ResolveOrganizationContext;
use App\Modules\Organization\Application\Ports\Out\IdentityAccess;
use App\Modules\Organization\Application\Ports\Out\MembershipStore;
use App\Modules\Organization\Application\Ports\Out\OrganizationModuleAvailability;
use App\Modules\Organization\Application\Ports\Out\OrganizationPreferenceStore;
use App\Modules\Organization\Application\Ports\Out\OrganizationStore;
use App\Modules\Organization\Application\Services\OrganizationChangeRecorder;
use App\Modules\Organization\Domain\Entities\Organization;
use App\Modules\Organization\Domain\Exceptions\InvalidOrganizationContext;
use App\Modules\Organization\Domain\ValueObjects\OrganizationContext;
use App\Modules\Organization\Domain\ValueObjects\OrganizationContextSource;
use App\Shared\Application\Contracts\Clock;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\ValueObjects\CorrelationContext;
use App\Shared\Domain\ValueObjects\UuidV7;

final readonly class OrganizationSelectionHandler implements OrganizationSelection
{
    public function __construct(
        private IdentityAccess $identities,
        private OrganizationStore $organizations,
        private MembershipStore $memberships,
        private OrganizationPreferenceStore $preferences,
        private OrganizationModuleAvailability $modules,
        private ResolveOrganizationContext $contextResolver,
        private OrganizationChangeRecorder $changes,
        private Clock $clock,
        private TransactionManager $transactions,
    ) {}

    public function organizationsFor(UuidV7 $identityId, ?UuidV7 $moduleId = null): array
    {
        $identity = $this->identities->get($identityId);

        if ($identity === null || ! $identity->active) {
            return [];
        }

        $organizationIds = $this->memberships->activeOrganizationIdsFor($identityId);

        if ($moduleId !== null) {
            $organizationIds = $this->modules->filterEnabledOrganizationIds($moduleId, $organizationIds);
        }

        $preferredId = $this->preferences->findLastOrganizationId($identityId);

        return array_map(
            static fn (Organization $organization): OrganizationOption => new OrganizationOption(
                id: $organization->id()->toString(),
                identifier: $organization->identifier()->value,
                name: $organization->name(),
                mfaPolicy: $organization->mfaPolicy()->value,
                preferred: $preferredId?->equals($organization->id()) ?? false,
            ),
            $this->organizations->findOperationalByIds($organizationIds),
        );
    }

    public function modulesFor(OrganizationContext $context): array
    {
        $validated = $this->contextResolver->resolve(new ResolveOrganizationContextData(
            identityId: $context->identityId,
            organizationId: $context->organizationId,
            moduleId: $context->moduleId,
            source: $context->source,
        ));

        if ($validated->authorizationVersion !== $context->authorizationVersion) {
            throw new InvalidOrganizationContext;
        }

        $modules = $this->modules->enabledModulesFor($context->organizationId);

        if ($context->moduleId === null) {
            return $modules;
        }

        return array_values(array_filter(
            $modules,
            static fn (AvailableModule $module): bool => $module->id === $context->moduleId->toString(),
        ));
    }

    public function preferred(
        UuidV7 $identityId,
        ?UuidV7 $moduleId,
        OrganizationContextSource $source,
    ): ?OrganizationContext {
        $organizationId = $this->preferences->findLastOrganizationId($identityId);

        if ($organizationId === null) {
            return null;
        }

        try {
            return $this->contextResolver->resolve(new ResolveOrganizationContextData(
                identityId: $identityId,
                organizationId: $organizationId,
                moduleId: $moduleId,
                source: $source,
            ));
        } catch (InvalidOrganizationContext) {
            return null;
        }
    }

    public function remember(OrganizationContext $context, CorrelationContext $correlation): void
    {
        $this->transactions->run(function () use ($context, $correlation): void {
            $validated = $this->contextResolver->resolve(new ResolveOrganizationContextData(
                identityId: $context->identityId,
                organizationId: $context->organizationId,
                moduleId: $context->moduleId,
                source: $context->source,
            ));

            if ($validated->authorizationVersion !== $context->authorizationVersion) {
                throw new InvalidOrganizationContext;
            }

            $now = $this->clock->now();
            $this->preferences->save($context->identityId, $context->organizationId, $now);
            $this->changes->record(
                targetType: 'identity_preference',
                targetId: $context->identityId,
                organizationId: $context->organizationId,
                actor: AuditActor::identity($context->identityId),
                correlation: $correlation,
                action: 'organization.preference.remembered',
                eventName: 'organization.preference.changed.v1',
                occurredAt: $now,
                after: ['last_organization_id' => $context->organizationId->toString()],
                eventPayload: [
                    'identity_id' => $context->identityId->toString(),
                    'last_organization_id' => $context->organizationId->toString(),
                ],
            );
        });
    }
}
