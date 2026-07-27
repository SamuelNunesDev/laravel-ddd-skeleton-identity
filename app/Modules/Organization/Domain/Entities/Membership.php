<?php

declare(strict_types=1);

namespace App\Modules\Organization\Domain\Entities;

use App\Modules\Organization\Domain\Exceptions\MembershipStateConflict;
use App\Modules\Organization\Domain\ValueObjects\MembershipStatus;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use InvalidArgumentException;

final class Membership
{
    public function __construct(
        private readonly UuidV7 $id,
        private readonly UuidV7 $identityId,
        private readonly UuidV7 $organizationId,
        private MembershipStatus $status,
        private readonly DateTimeImmutable $startedAt,
        private ?DateTimeImmutable $endedAt,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
        if (($this->status === MembershipStatus::Active && $this->endedAt !== null)
            || ($this->status === MembershipStatus::Ended
                && ($this->endedAt === null || $this->endedAt < $this->startedAt))) {
            throw new InvalidArgumentException('Membership status and validity period are inconsistent.');
        }
    }

    public static function create(
        UuidV7 $id,
        UuidV7 $identityId,
        UuidV7 $organizationId,
        DateTimeImmutable $now,
    ): self {
        return new self(
            id: $id,
            identityId: $identityId,
            organizationId: $organizationId,
            status: MembershipStatus::Active,
            startedAt: $now,
            endedAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function end(DateTimeImmutable $now): void
    {
        if ($this->status === MembershipStatus::Ended) {
            throw new MembershipStateConflict('Membership is already ended.');
        }

        $this->status = MembershipStatus::Ended;
        $this->endedAt = $now;
        $this->updatedAt = $now;
    }

    public function isActive(): bool
    {
        return $this->status === MembershipStatus::Active && $this->endedAt === null;
    }

    public function id(): UuidV7
    {
        return $this->id;
    }

    public function identityId(): UuidV7
    {
        return $this->identityId;
    }

    public function organizationId(): UuidV7
    {
        return $this->organizationId;
    }

    public function status(): MembershipStatus
    {
        return $this->status;
    }

    public function startedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function endedAt(): ?DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
