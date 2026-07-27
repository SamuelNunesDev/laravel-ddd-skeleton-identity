<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\DTO;

use App\Modules\Organization\Domain\Entities\Membership;
use DateTimeImmutable;

final readonly class MembershipDetails
{
    public function __construct(
        public string $id,
        public string $identityId,
        public string $organizationId,
        public string $status,
        public DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $endedAt,
    ) {}

    public static function fromMembership(Membership $membership): self
    {
        return new self(
            id: $membership->id()->toString(),
            identityId: $membership->identityId()->toString(),
            organizationId: $membership->organizationId()->toString(),
            status: $membership->status()->value,
            startedAt: $membership->startedAt(),
            endedAt: $membership->endedAt(),
        );
    }
}
