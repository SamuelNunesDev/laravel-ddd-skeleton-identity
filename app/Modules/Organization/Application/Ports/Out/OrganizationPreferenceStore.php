<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\Ports\Out;

use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;

interface OrganizationPreferenceStore
{
    public function findLastOrganizationId(UuidV7 $identityId): ?UuidV7;

    public function save(UuidV7 $identityId, UuidV7 $organizationId, DateTimeImmutable $updatedAt): void;
}
