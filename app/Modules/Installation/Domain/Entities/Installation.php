<?php

declare(strict_types=1);

namespace App\Modules\Installation\Domain\Entities;

use App\Modules\Installation\Domain\ValueObjects\InstallationSettings;
use App\Modules\Installation\Domain\ValueObjects\InstallationState;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;

final class Installation
{
    public function __construct(
        private readonly UuidV7 $id,
        private UuidV7 $ownerIdentityId,
        private InstallationState $state,
        private InstallationSettings $settings,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public function updateSettings(InstallationSettings $settings, DateTimeImmutable $now): void
    {
        $this->settings = $settings;
        $this->updatedAt = $now;
    }

    public function id(): UuidV7
    {
        return $this->id;
    }

    public function ownerIdentityId(): UuidV7
    {
        return $this->ownerIdentityId;
    }

    public function state(): InstallationState
    {
        return $this->state;
    }

    public function settings(): InstallationSettings
    {
        return $this->settings;
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
