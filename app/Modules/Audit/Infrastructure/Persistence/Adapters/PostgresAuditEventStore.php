<?php

declare(strict_types=1);

namespace App\Modules\Audit\Infrastructure\Persistence\Adapters;

use App\Modules\Audit\Application\Ports\Out\AuditEventStore;
use App\Modules\Audit\Domain\Entities\AuditEvent;
use Illuminate\Database\DatabaseManager;

final readonly class PostgresAuditEventStore implements AuditEventStore
{
    public function __construct(private DatabaseManager $database) {}

    public function append(AuditEvent $event): void
    {
        $this->database->connection()->table('audit_events')->insert([
            'id' => $event->id->toString(),
            'occurred_at' => $event->occurredAt->format('Y-m-d H:i:s.uP'),
            'actor_type' => $event->actor->type->value,
            'actor_id' => $event->actor->id?->toString(),
            'action' => $event->action,
            'target_type' => $event->target?->type,
            'target_id' => $event->target?->id->toString(),
            'organization_id' => $event->organizationId?->toString(),
            'module_id' => $event->moduleId?->toString(),
            'request_id' => (string) $event->correlation->requestId,
            'trace_id' => (string) $event->correlation->traceId,
            'session_id' => $event->sessionId,
            'ip_address' => $event->ipAddress,
            'outcome' => $event->outcome->value,
            'sensitivity' => $event->sensitivity->value,
            'before_values' => $this->encode($event->beforeValues),
            'after_values' => $this->encode($event->afterValues),
            'metadata' => $this->encode($event->metadata),
        ]);
    }

    /**
     * @param  array<array-key, mixed>|null  $value
     */
    private function encode(?array $value): ?string
    {
        return $value === null ? null : json_encode($value, JSON_THROW_ON_ERROR);
    }
}
