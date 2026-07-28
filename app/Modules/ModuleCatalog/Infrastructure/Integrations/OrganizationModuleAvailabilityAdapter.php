<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Infrastructure\Integrations;

use App\Modules\ModuleCatalog\Application\Ports\Out\ModuleStore;
use App\Modules\ModuleCatalog\Application\Ports\Out\OrganizationModuleStore;
use App\Modules\ModuleCatalog\Domain\Entities\ModuleDefinition;
use App\Modules\Organization\Application\DTO\AvailableModule;
use App\Modules\Organization\Application\Ports\Out\OrganizationModuleAvailability;
use App\Shared\Domain\ValueObjects\UuidV7;

final readonly class OrganizationModuleAvailabilityAdapter implements OrganizationModuleAvailability
{
    public function __construct(
        private ModuleStore $modules,
        private OrganizationModuleStore $organizationModules,
    ) {}

    public function isEnabled(UuidV7 $organizationId, UuidV7 $moduleId): bool
    {
        $module = $this->modules->findById($moduleId);

        return $module !== null
            && $module->isOperational()
            && $this->organizationModules->findEnabled($organizationId, $moduleId) !== null;
    }

    public function filterEnabledOrganizationIds(UuidV7 $moduleId, array $organizationIds): array
    {
        $module = $this->modules->findById($moduleId);

        if ($module === null || ! $module->isOperational()) {
            return [];
        }

        return $this->organizationModules->filterEnabledOrganizationIds($moduleId, $organizationIds);
    }

    public function enabledModulesFor(UuidV7 $organizationId): array
    {
        $modules = $this->modules->findOperationalByIds(
            $this->organizationModules->enabledModuleIdsFor($organizationId),
        );

        return array_map(
            static fn (ModuleDefinition $module): AvailableModule => new AvailableModule(
                id: $module->id()->toString(),
                identifier: $module->identifier()->value,
                name: $module->name(),
            ),
            $modules,
        );
    }
}
