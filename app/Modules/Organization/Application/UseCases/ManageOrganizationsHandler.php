<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\UseCases;

use App\Modules\Organization\Application\DTO\CreateOrganizationData;
use App\Modules\Organization\Application\DTO\OrganizationDetails;
use App\Modules\Organization\Application\DTO\OrganizationLifecycleData;
use App\Modules\Organization\Application\DTO\UpdateOrganizationData;
use App\Modules\Organization\Application\Ports\In\ManageOrganizations;
use App\Modules\Organization\Application\Ports\Out\OrganizationStore;
use App\Modules\Organization\Application\Services\OrganizationChangeRecorder;
use App\Modules\Organization\Domain\Entities\Organization;
use App\Modules\Organization\Domain\Exceptions\OrganizationIdentifierAlreadyExists;
use App\Modules\Organization\Domain\Exceptions\OrganizationNotFound;
use App\Modules\Organization\Domain\ValueObjects\OrganizationIdentifier;
use App\Shared\Application\Contracts\Clock;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Application\Contracts\UuidGenerator;
use App\Shared\Domain\ValueObjects\UuidV7;

final readonly class ManageOrganizationsHandler implements ManageOrganizations
{
    public function __construct(
        private OrganizationStore $organizations,
        private OrganizationChangeRecorder $changes,
        private UuidGenerator $uuidGenerator,
        private Clock $clock,
        private TransactionManager $transactions,
    ) {}

    public function create(CreateOrganizationData $data): OrganizationDetails
    {
        $identifier = new OrganizationIdentifier($data->identifier);

        return $this->transactions->run(function () use ($data, $identifier): OrganizationDetails {
            if ($this->organizations->identifierExists($identifier)) {
                throw new OrganizationIdentifierAlreadyExists;
            }

            $now = $this->clock->now();
            $organization = Organization::create(
                id: $this->uuidGenerator->generate(),
                identifier: $identifier,
                name: $data->name,
                mfaPolicy: $data->mfaPolicy,
                now: $now,
            );

            $this->organizations->insert($organization);
            $this->recordOrganizationChange(
                organization: $organization,
                data: $data,
                action: 'organization.created',
                eventName: 'organization.created.v1',
                occurredAt: $now,
                after: $this->snapshot($organization),
            );

            return OrganizationDetails::fromOrganization($organization);
        });
    }

    public function get(UuidV7 $organizationId): OrganizationDetails
    {
        $organization = $this->organizations->findById($organizationId);

        if ($organization === null) {
            throw new OrganizationNotFound;
        }

        return OrganizationDetails::fromOrganization($organization);
    }

    public function update(UpdateOrganizationData $data): OrganizationDetails
    {
        return $this->transactions->run(function () use ($data): OrganizationDetails {
            $organization = $this->findForUpdate($data->organizationId);
            $before = $this->snapshot($organization);
            $now = $this->clock->now();
            $organization->update($data->name, $data->mfaPolicy, $now);
            $this->organizations->update($organization);
            $this->changes->record(
                targetType: 'organization',
                targetId: $organization->id(),
                organizationId: $organization->id(),
                actor: $data->actor,
                correlation: $data->correlation,
                action: 'organization.updated',
                eventName: 'organization.updated.v1',
                occurredAt: $now,
                before: $before,
                after: $this->snapshot($organization),
                eventPayload: $this->snapshot($organization),
            );

            return OrganizationDetails::fromOrganization($organization);
        });
    }

    public function deactivate(OrganizationLifecycleData $data): OrganizationDetails
    {
        return $this->changeLifecycle($data, 'deactivate');
    }

    public function reactivate(OrganizationLifecycleData $data): OrganizationDetails
    {
        return $this->changeLifecycle($data, 'reactivate');
    }

    public function softDelete(OrganizationLifecycleData $data): OrganizationDetails
    {
        return $this->changeLifecycle($data, 'soft_delete');
    }

    public function restore(OrganizationLifecycleData $data): OrganizationDetails
    {
        return $this->changeLifecycle($data, 'restore');
    }

    private function changeLifecycle(OrganizationLifecycleData $data, string $operation): OrganizationDetails
    {
        return $this->transactions->run(function () use ($data, $operation): OrganizationDetails {
            $organization = $this->findForUpdate($data->organizationId);
            $before = $this->snapshot($organization);
            $now = $this->clock->now();

            if ($operation === 'deactivate') {
                $organization->deactivate($now);
            } elseif ($operation === 'reactivate') {
                $organization->reactivate($now);
            } elseif ($operation === 'soft_delete') {
                $organization->softDelete($now);
            } else {
                $organization->restore($now);
            }

            $this->organizations->update($organization);
            $this->changes->record(
                targetType: 'organization',
                targetId: $organization->id(),
                organizationId: $organization->id(),
                actor: $data->actor,
                correlation: $data->correlation,
                action: 'organization.'.$operation,
                eventName: 'organization.lifecycle.changed.v1',
                occurredAt: $now,
                before: $before,
                after: $this->snapshot($organization),
                metadata: ['reason' => $data->reason],
                eventPayload: $this->snapshot($organization),
            );

            return OrganizationDetails::fromOrganization($organization);
        });
    }

    private function findForUpdate(UuidV7 $organizationId): Organization
    {
        $organization = $this->organizations->findById($organizationId, forUpdate: true);

        if ($organization === null) {
            throw new OrganizationNotFound;
        }

        return $organization;
    }

    /**
     * @return array<string, string|null>
     */
    private function snapshot(Organization $organization): array
    {
        return [
            'identifier' => $organization->identifier()->value,
            'name' => $organization->name(),
            'mfa_policy' => $organization->mfaPolicy()->value,
            'status' => $organization->status()->value,
            'deleted_at' => $organization->deletedAt()?->format(DATE_ATOM),
        ];
    }

    /**
     * @param  array<string, string|null>  $after
     */
    private function recordOrganizationChange(
        Organization $organization,
        CreateOrganizationData $data,
        string $action,
        string $eventName,
        \DateTimeImmutable $occurredAt,
        array $after,
    ): void {
        $this->changes->record(
            targetType: 'organization',
            targetId: $organization->id(),
            organizationId: $organization->id(),
            actor: $data->actor,
            correlation: $data->correlation,
            action: $action,
            eventName: $eventName,
            occurredAt: $occurredAt,
            after: $after,
            eventPayload: $after,
        );
    }
}
