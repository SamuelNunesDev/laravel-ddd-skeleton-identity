<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Application\Services;

use App\Modules\Audit\Application\DTO\AuditEventData;
use App\Modules\Audit\Application\Ports\In\RecordAuditEvent;
use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Modules\Audit\Domain\ValueObjects\AuditOutcome;
use App\Modules\Audit\Domain\ValueObjects\AuditSensitivity;
use App\Modules\Audit\Domain\ValueObjects\AuditTarget;
use App\Shared\Application\Contracts\IntegrationEventPublisher;
use App\Shared\Application\Integration\IntegrationEventMessage;
use App\Shared\Domain\ValueObjects\CorrelationContext;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;

final readonly class ModuleCatalogChangeRecorder
{
    public function __construct(
        private RecordAuditEvent $audit,
        private IntegrationEventPublisher $events,
    ) {}

    /**
     * @param  array<array-key, mixed>|null  $before
     * @param  array<array-key, mixed>|null  $after
     * @param  array<array-key, mixed>  $metadata
     * @param  array<array-key, mixed>  $eventPayload
     */
    public function record(
        string $targetType,
        UuidV7 $targetId,
        UuidV7 $moduleId,
        ?UuidV7 $organizationId,
        AuditActor $actor,
        CorrelationContext $correlation,
        string $action,
        string $eventName,
        DateTimeImmutable $occurredAt,
        ?array $before = null,
        ?array $after = null,
        array $metadata = [],
        array $eventPayload = [],
    ): void {
        $this->audit->record(new AuditEventData(
            action: $action,
            actor: $actor,
            outcome: AuditOutcome::Succeeded,
            sensitivity: AuditSensitivity::Sensitive,
            correlation: $correlation,
            target: new AuditTarget($targetType, $targetId),
            organizationId: $organizationId,
            moduleId: $moduleId,
            beforeValues: $before,
            afterValues: $after,
            metadata: $metadata,
            occurredAt: $occurredAt,
        ));

        $this->events->publish(new IntegrationEventMessage(
            name: $eventName,
            aggregateType: $targetType,
            aggregateId: $targetId,
            occurredAt: $occurredAt,
            payload: [
                'module_id' => $moduleId->toString(),
                'organization_id' => $organizationId?->toString(),
                ...$eventPayload,
            ],
        ));
    }
}
