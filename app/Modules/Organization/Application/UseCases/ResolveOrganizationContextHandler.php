<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\UseCases;

use App\Modules\Organization\Application\DTO\ResolveOrganizationContextData;
use App\Modules\Organization\Application\Ports\In\ResolveOrganizationContext;
use App\Modules\Organization\Application\Ports\Out\IdentityAccess;
use App\Modules\Organization\Application\Ports\Out\MembershipStore;
use App\Modules\Organization\Application\Ports\Out\OrganizationModuleAvailability;
use App\Modules\Organization\Application\Ports\Out\OrganizationStore;
use App\Modules\Organization\Domain\Exceptions\InvalidOrganizationContext;
use App\Modules\Organization\Domain\ValueObjects\OrganizationContext;

final readonly class ResolveOrganizationContextHandler implements ResolveOrganizationContext
{
    public function __construct(
        private IdentityAccess $identities,
        private OrganizationStore $organizations,
        private MembershipStore $memberships,
        private OrganizationModuleAvailability $modules,
    ) {}

    public function resolve(ResolveOrganizationContextData $data): OrganizationContext
    {
        $identity = $this->identities->get($data->identityId);
        $organization = $this->organizations->findById($data->organizationId);
        $membership = $this->memberships->findActive($data->identityId, $data->organizationId);

        if ($identity === null
            || ! $identity->active
            || $organization === null
            || ! $organization->isOperational()
            || $membership === null
            || ($data->moduleId !== null && ! $this->modules->isEnabled($data->organizationId, $data->moduleId))) {
            throw new InvalidOrganizationContext;
        }

        return new OrganizationContext(
            identityId: $data->identityId,
            organizationId: $data->organizationId,
            moduleId: $data->moduleId,
            source: $data->source,
            authorizationVersion: $identity->authorizationVersion,
        );
    }
}
