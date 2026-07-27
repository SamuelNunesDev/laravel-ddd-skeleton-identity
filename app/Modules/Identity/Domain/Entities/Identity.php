<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Entities;

use App\Modules\Identity\Domain\Exceptions\IdentityStateConflict;
use App\Modules\Identity\Domain\ValueObjects\EmailAddress;
use App\Modules\Identity\Domain\ValueObjects\IdentityStatus;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use InvalidArgumentException;

final class Identity
{
    private function __construct(
        private readonly UuidV7 $id,
        private EmailAddress $email,
        private string $displayName,
        private IdentityStatus $status,
        private bool $mustChangePassword,
        private int $authorizationVersion,
        private ?DateTimeImmutable $disabledAt,
        private ?DateTimeImmutable $deletedAt,
        private ?DateTimeImmutable $restoredAt,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
        $this->assertDisplayName($this->displayName);

        if ($this->authorizationVersion < 1) {
            throw new InvalidArgumentException('Authorization version must be positive.');
        }
    }

    public static function create(
        UuidV7 $id,
        EmailAddress $email,
        string $displayName,
        DateTimeImmutable $now,
    ): self {
        return new self(
            id: $id,
            email: $email,
            displayName: trim($displayName),
            status: IdentityStatus::Active,
            mustChangePassword: true,
            authorizationVersion: 1,
            disabledAt: null,
            deletedAt: null,
            restoredAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function reconstitute(
        UuidV7 $id,
        EmailAddress $email,
        string $displayName,
        IdentityStatus $status,
        bool $mustChangePassword,
        int $authorizationVersion,
        ?DateTimeImmutable $disabledAt,
        ?DateTimeImmutable $deletedAt,
        ?DateTimeImmutable $restoredAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            $id,
            $email,
            $displayName,
            $status,
            $mustChangePassword,
            $authorizationVersion,
            $disabledAt,
            $deletedAt,
            $restoredAt,
            $createdAt,
            $updatedAt,
        );
    }

    public function updateProfile(EmailAddress $email, string $displayName, DateTimeImmutable $now): void
    {
        $this->assertNotDeleted();
        $this->assertDisplayName($displayName);
        $this->email = $email;
        $this->displayName = trim($displayName);
        $this->updatedAt = $now;
    }

    public function deactivate(DateTimeImmutable $now): void
    {
        $this->assertNotDeleted();

        if ($this->status === IdentityStatus::Disabled) {
            throw new IdentityStateConflict('Identity is already disabled.');
        }

        $this->status = IdentityStatus::Disabled;
        $this->disabledAt = $now;
        $this->updatedAt = $now;
        $this->authorizationVersion++;
    }

    public function reactivate(DateTimeImmutable $now): void
    {
        $this->assertNotDeleted();

        if ($this->status === IdentityStatus::Active) {
            throw new IdentityStateConflict('Identity is already active.');
        }

        $this->status = IdentityStatus::Active;
        $this->disabledAt = null;
        $this->updatedAt = $now;
        $this->authorizationVersion++;
    }

    public function softDelete(DateTimeImmutable $now): void
    {
        $this->assertNotDeleted();
        $this->status = IdentityStatus::Disabled;
        $this->disabledAt ??= $now;
        $this->deletedAt = $now;
        $this->updatedAt = $now;
        $this->authorizationVersion++;
    }

    public function restore(DateTimeImmutable $now): void
    {
        if ($this->deletedAt === null) {
            throw new IdentityStateConflict('Identity is not soft deleted.');
        }

        $this->status = IdentityStatus::Disabled;
        $this->disabledAt ??= $now;
        $this->deletedAt = null;
        $this->restoredAt = $now;
        $this->updatedAt = $now;
        $this->authorizationVersion++;
    }

    public function credentialChanged(bool $temporary, DateTimeImmutable $now): void
    {
        $this->assertNotDeleted();
        $this->mustChangePassword = $temporary;
        $this->updatedAt = $now;
        $this->authorizationVersion++;
    }

    public function canAuthenticate(): bool
    {
        return $this->status === IdentityStatus::Active && $this->deletedAt === null;
    }

    public function id(): UuidV7
    {
        return $this->id;
    }

    public function email(): EmailAddress
    {
        return $this->email;
    }

    public function displayName(): string
    {
        return $this->displayName;
    }

    public function status(): IdentityStatus
    {
        return $this->status;
    }

    public function mustChangePassword(): bool
    {
        return $this->mustChangePassword;
    }

    public function authorizationVersion(): int
    {
        return $this->authorizationVersion;
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
            throw new IdentityStateConflict('Soft-deleted identity cannot be changed before restoration.');
        }
    }

    private function assertDisplayName(string $displayName): void
    {
        $length = mb_strlen(trim($displayName), 'UTF-8');

        if ($length < 1 || $length > 160) {
            throw new InvalidArgumentException('Identity display name must contain between 1 and 160 characters.');
        }
    }
}
