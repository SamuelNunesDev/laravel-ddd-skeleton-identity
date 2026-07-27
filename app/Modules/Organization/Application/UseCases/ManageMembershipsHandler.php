<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\UseCases;

use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Modules\Organization\Application\DTO\CreateMembershipData;
use App\Modules\Organization\Application\DTO\EndMembershipData;
use App\Modules\Organization\Application\DTO\MembershipDetails;
use App\Modules\Organization\Application\DTO\ResolveOrganizationContextData;
use App\Modules\Organization\Application\Ports\In\ManageMemberships;
use App\Modules\Organization\Application\Ports\In\ResolveOrganizationContext;
use App\Modules\Organization\Application\Ports\Out\IdentityAccess;
use App\Modules\Organization\Application\Ports\Out\MembershipStore;
use App\Modules\Organization\Application\Ports\Out\OrganizationStore;
use App\Modules\Organization\Application\Services\OrganizationChangeRecorder;
use App\Modules\Organization\Domain\Entities\Membership;
use App\Modules\Organization\Domain\Exceptions\InvalidOrganizationContext;
use App\Modules\Organization\Domain\Exceptions\MembershipAlreadyActive;
use App\Modules\Organization\Domain\Exceptions\MembershipNotFound;
use App\Modules\Organization\Domain\ValueObjects\OrganizationContext;
use App\Shared\Application\Contracts\Clock;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Application\Contracts\UuidGenerator;
use App\Shared\Domain\ValueObjects\CorrelationContext;

final readonly class ManageMembershipsHandler implements ManageMemberships
{
    public function __construct(
        private MembershipStore $memberships,
        private OrganizationStore $organizations,
        private IdentityAccess $identities,
        private ResolveOrganizationContext $contextResolver,
        private OrganizationChangeRecorder $changes,
        private UuidGenerator $uuidGenerator,
        private Clock $clock,
        private TransactionManager $transactions,
    ) {}

    public function add(CreateMembershipData $data): MembershipDetails
    {
        return $this->transactions->run(function () use ($data): MembershipDetails {
            $identity = $this->identities->get($data->identityId);
            $organization = $this->organizations->findById($data->organizationId, forUpdate: true);

            if ($identity === null || ! $identity->active || $organization === null || ! $organization->isOperational()) {
                throw new InvalidOrganizationContext;
            }

            if ($this->memberships->findActive($data->identityId, $data->organizationId, forUpdate: true) !== null) {
                throw new MembershipAlreadyActive;
            }

            $now = $this->clock->now();
            $membership = Membership::create(
                id: $this->uuidGenerator->generate(),
                identityId: $data->identityId,
                organizationId: $data->organizationId,
                now: $now,
            );

            $this->memberships->insert($membership);
            $this->record($membership, $data->actor, $data->correlation, 'membership.created', $now, $data->reason);

            return MembershipDetails::fromMembership($membership);
        });
    }

    public function end(EndMembershipData $data): MembershipDetails
    {
        return $this->transactions->run(function () use ($data): MembershipDetails {
            $membership = $this->memberships->findById($data->membershipId, forUpdate: true);

            if ($membership === null) {
                throw new MembershipNotFound;
            }

            if (! $membership->organizationId()->equals($data->organizationId)) {
                throw new InvalidOrganizationContext;
            }

            $now = $this->clock->now();
            $membership->end($now);
            $this->memberships->update($membership);
            $this->record($membership, $data->actor, $data->correlation, 'membership.ended', $now, $data->reason);

            return MembershipDetails::fromMembership($membership);
        });
    }

    public function forOrganization(OrganizationContext $context): array
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

        return array_map(
            static fn (Membership $membership): MembershipDetails => MembershipDetails::fromMembership($membership),
            $this->memberships->forOrganization($context->organizationId),
        );
    }

    private function record(
        Membership $membership,
        AuditActor $actor,
        CorrelationContext $correlation,
        string $action,
        \DateTimeImmutable $occurredAt,
        ?string $reason,
    ): void {
        $snapshot = [
            'identity_id' => $membership->identityId()->toString(),
            'organization_id' => $membership->organizationId()->toString(),
            'status' => $membership->status()->value,
            'started_at' => $membership->startedAt()->format(DATE_ATOM),
            'ended_at' => $membership->endedAt()?->format(DATE_ATOM),
        ];
        $this->changes->record(
            targetType: 'membership',
            targetId: $membership->id(),
            organizationId: $membership->organizationId(),
            actor: $actor,
            correlation: $correlation,
            action: $action,
            eventName: 'membership.lifecycle.changed.v1',
            occurredAt: $occurredAt,
            after: $snapshot,
            metadata: ['reason' => $reason],
            eventPayload: $snapshot,
        );
    }
}
