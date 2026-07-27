<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Providers;

use App\Modules\Identity\Application\IdentitySecurityConfiguration;
use App\Modules\Identity\Application\Ports\In\CreateIdentity;
use App\Modules\Identity\Application\Ports\In\IdentityDirectory;
use App\Modules\Identity\Application\Ports\In\ManageIdentityCredentials;
use App\Modules\Identity\Application\Ports\In\ManageIdentityLifecycle;
use App\Modules\Identity\Application\Ports\Out\IdentityStore;
use App\Modules\Identity\Application\Ports\Out\PasswordCredentialStore;
use App\Modules\Identity\Application\Ports\Out\PasswordHasher;
use App\Modules\Identity\Application\UseCases\CreateIdentityHandler;
use App\Modules\Identity\Application\UseCases\IdentityCredentialsHandler;
use App\Modules\Identity\Application\UseCases\IdentityDirectoryHandler;
use App\Modules\Identity\Application\UseCases\IdentityLifecycleHandler;
use App\Modules\Identity\Infrastructure\Persistence\Adapters\PostgresIdentityStore;
use App\Modules\Identity\Infrastructure\Persistence\Adapters\PostgresPasswordCredentialStore;
use App\Modules\Identity\Infrastructure\Security\Argon2IdPasswordHasher;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Override;

final class IdentityServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(IdentitySecurityConfiguration::class, static function (Application $app): IdentitySecurityConfiguration {
            $config = $app->make(Repository::class);

            return new IdentitySecurityConfiguration(
                minimumPasswordLength: (int) $config->get('identity.password.minimum_length'),
                maximumPasswordBytes: (int) $config->get('identity.password.maximum_bytes'),
                temporaryPasswordHours: (int) $config->get('identity.temporary_password.lifetime_hours'),
                maximumTemporaryPasswordHours: (int) $config->get('identity.temporary_password.maximum_lifetime_hours'),
            );
        });
        $this->app->singleton(PasswordHasher::class, Argon2IdPasswordHasher::class);
        $this->app->singleton(IdentityStore::class, PostgresIdentityStore::class);
        $this->app->singleton(PasswordCredentialStore::class, PostgresPasswordCredentialStore::class);
        $this->app->singleton(CreateIdentity::class, CreateIdentityHandler::class);
        $this->app->singleton(IdentityDirectory::class, IdentityDirectoryHandler::class);
        $this->app->singleton(ManageIdentityLifecycle::class, IdentityLifecycleHandler::class);
        $this->app->singleton(ManageIdentityCredentials::class, IdentityCredentialsHandler::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Migrations');
    }
}
