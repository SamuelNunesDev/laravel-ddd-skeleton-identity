<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Domain\Entities;

use App\Modules\ModuleCatalog\Domain\Exceptions\OrganizationModuleNotEnabled;
use App\Modules\ModuleCatalog\Domain\ValueObjects\OrganizationModuleStatus;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use InvalidArgumentException;

final class OrganizationModule
{
    public function __construct(
        private readonly UuidV7 $id,
        private readonly UuidV7 $organizationId,
        private readonly UuidV7 $moduleId,
        private OrganizationModuleStatus $status,
        private readonly DateTimeImmutable $enabledAt,
        private ?DateTimeImmutable $disabledAt,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
        if (($this->status === OrganizationModuleStatus::Enabled && $this->disabledAt !== null)
            || ($this->status === OrganizationModuleStatus::Disabled
                && ($this->disabledAt === null || $this->disabledAt < $this->enabledAt))) {
            throw new InvalidArgumentException('Organization module status and validity period are inconsistent.');
        }
    }

    public static function enable(
        UuidV7 $id,
        UuidV7 $organizationId,
        UuidV7 $moduleId,
        DateTimeImmutable $now,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            moduleId: $moduleId,
            status: OrganizationModuleStatus::Enabled,
            enabledAt: $now,
            disabledAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function disable(DateTimeImmutable $now): void
    {
        if ($this->status !== OrganizationModuleStatus::Enabled || $this->disabledAt !== null) {
            throw new OrganizationModuleNotEnabled;
        }

        $this->status = OrganizationModuleStatus::Disabled;
        $this->disabledAt = $now;
        $this->updatedAt = $now;
    }

    public function id(): UuidV7
    {
        return $this->id;
    }

    public function organizationId(): UuidV7
    {
        return $this->organizationId;
    }

    public function moduleId(): UuidV7
    {
        return $this->moduleId;
    }

    public function status(): OrganizationModuleStatus
    {
        return $this->status;
    }

    public function enabledAt(): DateTimeImmutable
    {
        return $this->enabledAt;
    }

    public function disabledAt(): ?DateTimeImmutable
    {
        return $this->disabledAt;
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
