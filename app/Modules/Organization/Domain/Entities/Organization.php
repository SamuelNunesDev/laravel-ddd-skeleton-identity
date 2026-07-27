<?php

declare(strict_types=1);

namespace App\Modules\Organization\Domain\Entities;

use App\Modules\Organization\Domain\Exceptions\OrganizationStateConflict;
use App\Modules\Organization\Domain\ValueObjects\MfaPolicy;
use App\Modules\Organization\Domain\ValueObjects\OrganizationIdentifier;
use App\Modules\Organization\Domain\ValueObjects\OrganizationStatus;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use InvalidArgumentException;

final class Organization
{
    private function __construct(
        private readonly UuidV7 $id,
        private readonly OrganizationIdentifier $identifier,
        private string $name,
        private MfaPolicy $mfaPolicy,
        private OrganizationStatus $status,
        private ?DateTimeImmutable $disabledAt,
        private ?DateTimeImmutable $deletedAt,
        private ?DateTimeImmutable $restoredAt,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
        $this->assertName($this->name);
    }

    public static function create(
        UuidV7 $id,
        OrganizationIdentifier $identifier,
        string $name,
        MfaPolicy $mfaPolicy,
        DateTimeImmutable $now,
    ): self {
        return new self(
            id: $id,
            identifier: $identifier,
            name: trim($name),
            mfaPolicy: $mfaPolicy,
            status: OrganizationStatus::Active,
            disabledAt: null,
            deletedAt: null,
            restoredAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function reconstitute(
        UuidV7 $id,
        OrganizationIdentifier $identifier,
        string $name,
        MfaPolicy $mfaPolicy,
        OrganizationStatus $status,
        ?DateTimeImmutable $disabledAt,
        ?DateTimeImmutable $deletedAt,
        ?DateTimeImmutable $restoredAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            $id,
            $identifier,
            $name,
            $mfaPolicy,
            $status,
            $disabledAt,
            $deletedAt,
            $restoredAt,
            $createdAt,
            $updatedAt,
        );
    }

    public function update(string $name, MfaPolicy $mfaPolicy, DateTimeImmutable $now): void
    {
        $this->assertNotDeleted();
        $this->assertName($name);
        $this->name = trim($name);
        $this->mfaPolicy = $mfaPolicy;
        $this->updatedAt = $now;
    }

    public function deactivate(DateTimeImmutable $now): void
    {
        $this->assertNotDeleted();

        if ($this->status === OrganizationStatus::Disabled) {
            throw new OrganizationStateConflict('Organization is already disabled.');
        }

        $this->status = OrganizationStatus::Disabled;
        $this->disabledAt = $now;
        $this->updatedAt = $now;
    }

    public function reactivate(DateTimeImmutable $now): void
    {
        $this->assertNotDeleted();

        if ($this->status === OrganizationStatus::Active) {
            throw new OrganizationStateConflict('Organization is already active.');
        }

        $this->status = OrganizationStatus::Active;
        $this->disabledAt = null;
        $this->updatedAt = $now;
    }

    public function softDelete(DateTimeImmutable $now): void
    {
        $this->assertNotDeleted();
        $this->status = OrganizationStatus::Disabled;
        $this->disabledAt ??= $now;
        $this->deletedAt = $now;
        $this->updatedAt = $now;
    }

    public function restore(DateTimeImmutable $now): void
    {
        if ($this->deletedAt === null) {
            throw new OrganizationStateConflict('Organization is not soft deleted.');
        }

        $this->status = OrganizationStatus::Disabled;
        $this->disabledAt ??= $now;
        $this->deletedAt = null;
        $this->restoredAt = $now;
        $this->updatedAt = $now;
    }

    public function isOperational(): bool
    {
        return $this->status === OrganizationStatus::Active && $this->deletedAt === null;
    }

    public function id(): UuidV7
    {
        return $this->id;
    }

    public function identifier(): OrganizationIdentifier
    {
        return $this->identifier;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function mfaPolicy(): MfaPolicy
    {
        return $this->mfaPolicy;
    }

    public function status(): OrganizationStatus
    {
        return $this->status;
    }

    public function disabledAt(): ?DateTimeImmutable
    {
        return $this->disabledAt;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function restoredAt(): ?DateTimeImmutable
    {
        return $this->restoredAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function assertNotDeleted(): void
    {
        if ($this->deletedAt !== null) {
            throw new OrganizationStateConflict('Soft-deleted organization cannot be changed before restoration.');
        }
    }

    private function assertName(string $name): void
    {
        $length = mb_strlen(trim($name), 'UTF-8');

        if ($length < 1 || $length > 160) {
            throw new InvalidArgumentException('Organization name must contain between 1 and 160 characters.');
        }
    }
}
