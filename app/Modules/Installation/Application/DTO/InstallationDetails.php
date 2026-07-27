<?php

declare(strict_types=1);

namespace App\Modules\Installation\Application\DTO;

use App\Modules\Installation\Domain\Entities\Installation;

final readonly class InstallationDetails
{
    /**
     * @param  array<string, string|null>  $settings
     */
    public function __construct(
        public string $id,
        public string $ownerIdentityId,
        public string $state,
        public array $settings,
        public bool $created,
    ) {}

    public static function fromInstallation(Installation $installation, bool $created = false): self
    {
        return new self(
            id: $installation->id()->toString(),
            ownerIdentityId: $installation->ownerIdentityId()->toString(),
            state: $installation->state()->value,
            settings: $installation->settings()->toArray(),
            created: $created,
        );
    }
}
