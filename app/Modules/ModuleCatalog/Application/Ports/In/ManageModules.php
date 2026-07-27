<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Application\Ports\In;

use App\Modules\ModuleCatalog\Application\DTO\ModuleDetails;
use App\Modules\ModuleCatalog\Application\DTO\ModuleLifecycleData;
use App\Modules\ModuleCatalog\Application\DTO\RegisterModuleData;
use App\Modules\ModuleCatalog\Application\DTO\UpdateModuleData;
use App\Shared\Domain\ValueObjects\UuidV7;

interface ManageModules
{
    public function register(RegisterModuleData $data): ModuleDetails;

    public function get(UuidV7 $moduleId): ModuleDetails;

    public function update(UpdateModuleData $data): ModuleDetails;

    public function deactivate(ModuleLifecycleData $data): ModuleDetails;

    public function reactivate(ModuleLifecycleData $data): ModuleDetails;

    public function softDelete(ModuleLifecycleData $data): ModuleDetails;

    public function restore(ModuleLifecycleData $data): ModuleDetails;
}
