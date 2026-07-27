<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Application\Contracts\IntegrationEventPublisher;
use App\Shared\Application\Contracts\UuidGenerator;
use App\Shared\Application\Integration\IntegrationEventMessage;
use Illuminate\Database\DatabaseManager;

final readonly class PostgresOutboxPublisher implements IntegrationEventPublisher
{
    public function __construct(
        private DatabaseManager $database,
        private UuidGenerator $uuidGenerator,
    ) {}

    public function publish(IntegrationEventMessage $event): void
    {
        $occurredAt = $event->occurredAt->format('Y-m-d H:i:s.uP');

        $this->database->connection()->table('outbox_messages')->insert([
            'id' => $this->uuidGenerator->generate()->toString(),
            'event_name' => $event->name,
            'aggregate_type' => $event->aggregateType,
            'aggregate_id' => $event->aggregateId->toString(),
            'payload' => json_encode($event->payload, JSON_THROW_ON_ERROR),
            'occurred_at' => $occurredAt,
            'available_at' => $occurredAt,
            'published_at' => null,
            'attempts' => 0,
            'last_error' => null,
        ]);
    }
}
