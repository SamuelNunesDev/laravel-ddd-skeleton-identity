<?php

declare(strict_types=1);

namespace App\Modules\Organization\Infrastructure\Providers;

use App\Modules\Organization\Application\Ports\In\ManageMemberships;
use App\Modules\Organization\Application\Ports\In\ManageOrganizations;
use App\Modules\Organization\Application\Ports\In\OrganizationSelection;
use App\Modules\Organization\Application\Ports\In\ResolveOrganizationContext;
use App\Modules\Organization\Application\Ports\Out\IdentityAccess;
use App\Modules\Organization\Application\Ports\Out\MembershipStore;
use App\Modules\Organization\Application\Ports\Out\OrganizationPreferenceStore;
use App\Modules\Organization\Application\Ports\Out\OrganizationStore;
use App\Modules\Organization\Application\UseCases\ManageMembershipsHandler;
use App\Modules\Organization\Application\UseCases\ManageOrganizationsHandler;
use App\Modules\Organization\Application\UseCases\OrganizationSelectionHandler;
use App\Modules\Organization\Application\UseCases\ResolveOrganizationContextHandler;
use App\Modules\Organization\Infrastructure\Integrations\IdentityAccessAdapter;
use App\Modules\Organization\Infrastructure\Persistence\Adapters\PostgresMembershipStore;
use App\Modules\Organization\Infrastructure\Persistence\Adapters\PostgresOrganizationPreferenceStore;
use App\Modules\Organization\Infrastructure\Persistence\Adapters\PostgresOrganizationStore;
use Illuminate\Support\ServiceProvider;
use Override;

final class OrganizationServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(OrganizationStore::class, PostgresOrganizationStore::class);
        $this->app->singleton(MembershipStore::class, PostgresMembershipStore::class);
        $this->app->singleton(OrganizationPreferenceStore::class, PostgresOrganizationPreferenceStore::class);
        $this->app->singleton(IdentityAccess::class, IdentityAccessAdapter::class);
        $this->app->singleton(ManageOrganizations::class, ManageOrganizationsHandler::class);
        $this->app->singleton(ManageMemberships::class, ManageMembershipsHandler::class);
        $this->app->singleton(ResolveOrganizationContext::class, ResolveOrganizationContextHandler::class);
        $this->app->singleton(OrganizationSelection::class, OrganizationSelectionHandler::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Migrations');
    }
}
