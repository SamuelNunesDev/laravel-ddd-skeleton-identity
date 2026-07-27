<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Application\Ports\In;

use App\Modules\ModuleCatalog\Application\DTO\ChangeOrganizationModuleData;
use App\Modules\ModuleCatalog\Application\DTO\ModuleDetails;
use App\Modules\ModuleCatalog\Application\DTO\OrganizationModuleDetails;
use App\Modules\Organization\Domain\ValueObjects\OrganizationContext;
use App\Shared\Domain\ValueObjects\UuidV7;

interface ManageOrganizationModules
{
    public function enable(ChangeOrganizationModuleData $data): OrganizationModuleDetails;

    public function disable(ChangeOrganizationModuleData $data): OrganizationModuleDetails;

    /**
     * @return list<ModuleDetails>
     */
    public function enabledForOrganization(OrganizationContext $context): array;

    /**
     * @return list<string>
     */
    public function enabledOrganizationIdsFor(UuidV7 $moduleId): array;
}
