<?php

declare(strict_types=1);

namespace App\Modules\Installation\Infrastructure\Providers;

use App\Modules\Identity\Application\Ports\Out\IdentityLifecycleProtection;
use App\Modules\Installation\Application\InstallationDefaults;
use App\Modules\Installation\Application\Ports\In\InitializeInstallation;
use App\Modules\Installation\Application\Ports\In\InstallationSettings;
use App\Modules\Installation\Application\Ports\In\RecoverInstallationOwner;
use App\Modules\Installation\Application\Ports\Out\InstallationStore;
use App\Modules\Installation\Application\UseCases\InitializeInstallationHandler;
use App\Modules\Installation\Application\UseCases\InstallationSettingsHandler;
use App\Modules\Installation\Application\UseCases\RecoverInstallationOwnerHandler;
use App\Modules\Installation\Infrastructure\Console\RecoverInstallationOwnerCommand;
use App\Modules\Installation\Infrastructure\Integrations\InstallationOwnerProtectionAdapter;
use App\Modules\Installation\Infrastructure\Persistence\Adapters\PostgresInstallationStore;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Override;

final class InstallationServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(InstallationDefaults::class, static function (Application $app): InstallationDefaults {
            $config = $app->make(Repository::class);

            return new InstallationDefaults(
                displayName: (string) $config->get('app.name', 'Laravel'),
                locale: (string) $config->get('app.locale', 'en'),
                timezone: (string) $config->get('app.timezone', 'UTC'),
            );
        });
        $this->app->singleton(InstallationStore::class, PostgresInstallationStore::class);
        $this->app->singleton(IdentityLifecycleProtection::class, InstallationOwnerProtectionAdapter::class);
        $this->app->singleton(InitializeInstallation::class, InitializeInstallationHandler::class);
        $this->app->singleton(InstallationSettings::class, InstallationSettingsHandler::class);
        $this->app->singleton(RecoverInstallationOwner::class, RecoverInstallationOwnerHandler::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Migrations');
        $this->commands([RecoverInstallationOwnerCommand::class]);
    }
}
