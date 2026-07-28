<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\Ports\Out;

use App\Modules\Organization\Application\DTO\AvailableModule;
use App\Shared\Domain\ValueObjects\UuidV7;

interface OrganizationModuleAvailability
{
    public function isEnabled(UuidV7 $organizationId, UuidV7 $moduleId): bool;

    /**
     * @param  list<UuidV7>  $organizationIds
     * @return list<UuidV7>
     */
    public function filterEnabledOrganizationIds(UuidV7 $moduleId, array $organizationIds): array;

    /**
     * @return list<AvailableModule>
     */
    public function enabledModulesFor(UuidV7 $organizationId): array;
}
