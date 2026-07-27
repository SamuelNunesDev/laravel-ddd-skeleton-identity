<?php

declare(strict_types=1);

namespace App\Modules\Audit\Infrastructure\Providers;

use App\Modules\Audit\Application\Ports\In\RecordAuditEvent;
use App\Modules\Audit\Application\Ports\Out\AuditEventStore;
use App\Modules\Audit\Application\UseCases\RecordAuditEventHandler;
use App\Modules\Audit\Domain\Services\SensitiveDataRedactor;
use App\Modules\Audit\Infrastructure\Persistence\Adapters\PostgresAuditEventStore;
use Illuminate\Support\ServiceProvider;
use Override;

final class AuditServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(SensitiveDataRedactor::class);
        $this->app->singleton(AuditEventStore::class, PostgresAuditEventStore::class);
        $this->app->singleton(RecordAuditEvent::class, RecordAuditEventHandler::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Persistence/Migrations');
    }
}
