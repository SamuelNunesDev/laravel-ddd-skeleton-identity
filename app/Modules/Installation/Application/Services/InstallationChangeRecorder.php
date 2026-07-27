<?php

declare(strict_types=1);

namespace App\Modules\Installation\Application\Services;

use App\Modules\Audit\Application\DTO\AuditEventData;
use App\Modules\Audit\Application\Ports\In\RecordAuditEvent;
use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Modules\Audit\Domain\ValueObjects\AuditOutcome;
use App\Modules\Audit\Domain\ValueObjects\AuditSensitivity;
use App\Modules\Audit\Domain\ValueObjects\AuditTarget;
use App\Modules\Installation\Domain\Entities\Installation;
use App\Shared\Application\Contracts\IntegrationEventPublisher;
use App\Shared\Application\Integration\IntegrationEventMessage;
use App\Shared\Domain\ValueObjects\CorrelationContext;
use DateTimeImmutable;

final readonly class InstallationChangeRecorder
{
    public function __construct(
        private RecordAuditEvent $audit,
        private IntegrationEventPublisher $events,
    ) {}

    /**
     * @param  array<array-key, mixed>|null  $before
     * @param  array<array-key, mixed>|null  $after
     * @param  array<array-key, mixed>  $metadata
     */
    public function record(
        Installation $installation,
        AuditActor $actor,
        CorrelationContext $correlation,
        string $action,
        string $eventName,
        DateTimeImmutable $occurredAt,
        ?array $before = null,
        ?array $after = null,
        array $metadata = [],
    ): void {
        $this->audit->record(new AuditEventData(
            action: $action,
            actor: $actor,
            outcome: AuditOutcome::Succeeded,
            sensitivity: AuditSensitivity::Sensitive,
            correlation: $correlation,
            target: new AuditTarget('installation', $installation->id()),
            beforeValues: $before,
            afterValues: $after,
            metadata: $metadata,
            occurredAt: $occurredAt,
        ));

        $this->events->publish(new IntegrationEventMessage(
            name: $eventName,
            aggregateType: 'installation',
            aggregateId: $installation->id(),
            occurredAt: $occurredAt,
            payload: [
                'installation_id' => $installation->id()->toString(),
                'owner_identity_id' => $installation->ownerIdentityId()->toString(),
                'state' => $installation->state()->value,
            ],
        ));
    }
}
