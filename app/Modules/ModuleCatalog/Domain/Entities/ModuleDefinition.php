<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Domain\Entities;

use App\Modules\ModuleCatalog\Domain\Exceptions\ModuleStateConflict;
use App\Modules\ModuleCatalog\Domain\ValueObjects\ModuleIdentifier;
use App\Modules\ModuleCatalog\Domain\ValueObjects\ModuleStatus;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use InvalidArgumentException;

final class ModuleDefinition
{
    private function __construct(
        private readonly UuidV7 $id,
        private readonly ModuleIdentifier $identifier,
        private string $name,
        private string $description,
        private ModuleStatus $status,
        private ?DateTimeImmutable $disabledAt,
        private ?DateTimeImmutable $deletedAt,
        private ?DateTimeImmutable $restoredAt,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
        $this->assertName($this->name);
        $this->assertDescription($this->description);
    }

    public static function create(
        UuidV7 $id,
        ModuleIdentifier $identifier,
        string $name,
        string $description,
        DateTimeImmutable $now,
    ): self {
        return new self(
            id: $id,
            identifier: $identifier,
            name: trim($name),
            description: trim($description),
            status: ModuleStatus::Active,
            disabledAt: null,
            deletedAt: null,
            restoredAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function reconstitute(
        UuidV7 $id,
        ModuleIdentifier $identifier,
        string $name,
        string $description,
        ModuleStatus $status,
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
            $description,
            $status,
            $disabledAt,
            $deletedAt,
            $restoredAt,
            $createdAt,
            $updatedAt,
        );
    }

    public function update(string $name, string $description, DateTimeImmutable $now): void
    {
        $this->assertNotDeleted();
        $this->assertName($name);
        $this->assertDescription($description);
        $this->name = trim($name);
        $this->description = trim($description);
        $this->updatedAt = $now;
    }

    public function deactivate(DateTimeImmutable $now): void
    {
        $this->assertNotDeleted();

        if ($this->status === ModuleStatus::Disabled) {
            throw new ModuleStateConflict('Module is already disabled.');
        }

        $this->status = ModuleStatus::Disabled;
        $this->disabledAt = $now;
        $this->updatedAt = $now;
    }

    public function reactivate(DateTimeImmutable $now): void
    {
        $this->assertNotDeleted();

        if ($this->status === ModuleStatus::Active) {
            throw new ModuleStateConflict('Module is already active.');
        }

        $this->status = ModuleStatus::Active;
        $this->disabledAt = null;
        $this->updatedAt = $now;
    }

    public function softDelete(DateTimeImmutable $now): void
    {
        $this->assertNotDeleted();
        $this->status = ModuleStatus::Disabled;
        $this->disabledAt ??= $now;
        $this->deletedAt = $now;
        $this->updatedAt = $now;
    }

    public function restore(DateTimeImmutable $now): void
    {
        if ($this->deletedAt === null) {
            throw new ModuleStateConflict('Module is not soft deleted.');
        }

        $this->status = ModuleStatus::Disabled;
        $this->disabledAt ??= $now;
        $this->deletedAt = null;
        $this->restoredAt = $now;
        $this->updatedAt = $now;
    }

    public function isOperational(): bool
    {
        return $this->status === ModuleStatus::Active && $this->deletedAt === null;
    }

    public function id(): UuidV7
    {
        return $this->id;
    }

    public function identifier(): ModuleIdentifier
    {
        return $this->identifier;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function status(): ModuleStatus
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
            throw new ModuleStateConflict('Soft-deleted module cannot be changed before restoration.');
        }
    }

    private function assertName(string $name): void
    {
        $length = mb_strlen(trim($name), 'UTF-8');

        if ($length < 1 || $length > 160) {
            throw new InvalidArgumentException('Module name must contain between 1 and 160 characters.');
        }
    }

    private function assertDescription(string $description): void
    {
        $length = mb_strlen(trim($description), 'UTF-8');

        if ($length < 1 || $length > 2000) {
            throw new InvalidArgumentException('Module description must contain between 1 and 2000 characters.');
        }
    }
}
