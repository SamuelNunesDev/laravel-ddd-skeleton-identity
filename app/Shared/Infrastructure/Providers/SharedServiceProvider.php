<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Providers;

use App\Shared\Application\Contracts\Clock;
use App\Shared\Application\Contracts\IntegrationEventPublisher;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Application\Contracts\UuidGenerator;
use App\Shared\Infrastructure\Clock\SystemClock;
use App\Shared\Infrastructure\Identifiers\LaravelUuidV7Generator;
use App\Shared\Infrastructure\Persistence\LaravelTransactionManager;
use App\Shared\Infrastructure\Persistence\PostgresOutboxPublisher;
use Illuminate\Support\ServiceProvider;
use Override;

final class SharedServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(UuidGenerator::class, LaravelUuidV7Generator::class);
        $this->app->singleton(TransactionManager::class, LaravelTransactionManager::class);
        $this->app->singleton(IntegrationEventPublisher::class, PostgresOutboxPublisher::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Migrations');
    }
}
