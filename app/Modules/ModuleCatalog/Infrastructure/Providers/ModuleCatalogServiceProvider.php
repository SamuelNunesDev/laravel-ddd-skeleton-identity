<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Infrastructure\Providers;

use App\Modules\ModuleCatalog\Application\Ports\In\ManageModules;
use App\Modules\ModuleCatalog\Application\Ports\In\ManageOrganizationModules;
use App\Modules\ModuleCatalog\Application\Ports\Out\ModuleStore;
use App\Modules\ModuleCatalog\Application\Ports\Out\OrganizationModuleStore;
use App\Modules\ModuleCatalog\Application\UseCases\ManageModulesHandler;
use App\Modules\ModuleCatalog\Application\UseCases\ManageOrganizationModulesHandler;
use App\Modules\ModuleCatalog\Infrastructure\Integrations\OrganizationModuleAvailabilityAdapter;
use App\Modules\ModuleCatalog\Infrastructure\Persistence\Adapters\PostgresModuleStore;
use App\Modules\ModuleCatalog\Infrastructure\Persistence\Adapters\PostgresOrganizationModuleStore;
use App\Modules\Organization\Application\Ports\Out\OrganizationModuleAvailability;
use Illuminate\Support\ServiceProvider;
use Override;

final class ModuleCatalogServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(ModuleStore::class, PostgresModuleStore::class);
        $this->app->singleton(OrganizationModuleStore::class, PostgresOrganizationModuleStore::class);
        $this->app->singleton(OrganizationModuleAvailability::class, OrganizationModuleAvailabilityAdapter::class);
        $this->app->singleton(ManageModules::class, ManageModulesHandler::class);
        $this->app->singleton(ManageOrganizationModules::class, ManageOrganizationModulesHandler::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Migrations');
    }
}
