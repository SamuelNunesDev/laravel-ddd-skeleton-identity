<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Application\Ports\Out;

use App\Modules\ModuleCatalog\Domain\Entities\OrganizationModule;
use App\Shared\Domain\ValueObjects\UuidV7;

interface OrganizationModuleStore
{
    public function findEnabled(UuidV7 $organizationId, UuidV7 $moduleId, bool $forUpdate = false): ?OrganizationModule;

    public function insert(OrganizationModule $organizationModule): void;

    public function update(OrganizationModule $organizationModule): void;

    /**
     * @return list<UuidV7>
     */
    public function enabledModuleIdsFor(UuidV7 $organizationId): array;

    /**
     * @param  list<UuidV7>  $organizationIds
     * @return list<UuidV7>
     */
    public function filterEnabledOrganizationIds(UuidV7 $moduleId, array $organizationIds): array;

    /**
     * @return list<UuidV7>
     */
    public function enabledOrganizationIdsFor(UuidV7 $moduleId): array;
}
