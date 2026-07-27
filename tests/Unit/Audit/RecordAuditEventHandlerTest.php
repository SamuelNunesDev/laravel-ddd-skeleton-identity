<?php

declare(strict_types=1);

namespace Tests\Unit\Audit;

use App\Modules\Audit\Application\DTO\AuditEventData;
use App\Modules\Audit\Application\Ports\Out\AuditEventStore;
use App\Modules\Audit\Application\UseCases\RecordAuditEventHandler;
use App\Modules\Audit\Domain\Entities\AuditEvent;
use App\Modules\Audit\Domain\Services\SensitiveDataRedactor;
use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Modules\Audit\Domain\ValueObjects\AuditOutcome;
use App\Modules\Audit\Domain\ValueObjects\AuditSensitivity;
use App\Shared\Application\Contracts\Clock;
use App\Shared\Application\Contracts\UuidGenerator;
use App\Shared\Domain\ValueObjects\CorrelationContext;
use App\Shared\Domain\ValueObjects\RequestId;
use App\Shared\Domain\ValueObjects\TraceId;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RecordAuditEventHandlerTest extends TestCase
{
    public function test_a_store_failure_is_propagated_for_fail_closed_callers(): void
    {
        $store = new class implements AuditEventStore
        {
            public function append(AuditEvent $event): void
            {
                throw new RuntimeException('Audit storage unavailable.');
            }
        };

        $uuid = UuidV7::fromString('018f47a2-4b9d-7cc1-8b7a-112233445566');
        $clock = new class implements Clock
        {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-07-27T12:00:00+00:00');
            }
        };
        $generator = new class($uuid) implements UuidGenerator
        {
            public function __construct(private readonly UuidV7 $uuid) {}

            public function generate(): UuidV7
            {
                return $this->uuid;
            }
        };
        $handler = new RecordAuditEventHandler(
            store: $store,
            redactor: new SensitiveDataRedactor,
            uuidGenerator: $generator,
            clock: $clock,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Audit storage unavailable.');

        $handler->record(new AuditEventData(
            action: 'identity.created',
            actor: AuditActor::system(),
            outcome: AuditOutcome::Succeeded,
            sensitivity: AuditSensitivity::Sensitive,
            correlation: new CorrelationContext(
                requestId: new RequestId($uuid),
                traceId: TraceId::fromString('4bf92f3577b34da6a3ce929d0e0e4736'),
            ),
        ));
    }
}
