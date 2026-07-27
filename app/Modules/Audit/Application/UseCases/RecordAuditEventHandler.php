<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\UseCases;

use App\Modules\Audit\Application\DTO\AuditEventData;
use App\Modules\Audit\Application\Ports\In\RecordAuditEvent;
use App\Modules\Audit\Application\Ports\Out\AuditEventStore;
use App\Modules\Audit\Domain\Entities\AuditEvent;
use App\Modules\Audit\Domain\Services\SensitiveDataRedactor;
use App\Shared\Application\Contracts\Clock;
use App\Shared\Application\Contracts\UuidGenerator;
use App\Shared\Domain\ValueObjects\UuidV7;

final readonly class RecordAuditEventHandler implements RecordAuditEvent
{
    public function __construct(
        private AuditEventStore $store,
        private SensitiveDataRedactor $redactor,
        private UuidGenerator $uuidGenerator,
        private Clock $clock,
    ) {}

    public function record(AuditEventData $data): UuidV7
    {
        $event = new AuditEvent(
            id: $this->uuidGenerator->generate(),
            action: $data->action,
            actor: $data->actor,
            outcome: $data->outcome,
            sensitivity: $data->sensitivity,
            correlation: $data->correlation,
            occurredAt: $data->occurredAt ?? $this->clock->now(),
            target: $data->target,
            organizationId: $data->organizationId,
            moduleId: $data->moduleId,
            beforeValues: $data->beforeValues === null ? null : $this->redactor->redact($data->beforeValues),
            afterValues: $data->afterValues === null ? null : $this->redactor->redact($data->afterValues),
            metadata: $this->redactor->redact($data->metadata),
            ipAddress: $data->ipAddress,
            sessionId: $data->sessionId,
        );

        $this->store->append($event);

        return $event->id;
    }
}
