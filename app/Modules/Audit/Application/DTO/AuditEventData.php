<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\DTO;

use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Modules\Audit\Domain\ValueObjects\AuditOutcome;
use App\Modules\Audit\Domain\ValueObjects\AuditSensitivity;
use App\Modules\Audit\Domain\ValueObjects\AuditTarget;
use App\Shared\Domain\ValueObjects\CorrelationContext;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;

final readonly class AuditEventData
{
    /**
     * @param  array<array-key, mixed>|null  $beforeValues
     * @param  array<array-key, mixed>|null  $afterValues
     * @param  array<array-key, mixed>  $metadata
     */
    public function __construct(
        public string $action,
        public AuditActor $actor,
        public AuditOutcome $outcome,
        public AuditSensitivity $sensitivity,
        public CorrelationContext $correlation,
        public ?AuditTarget $target = null,
        public ?UuidV7 $organizationId = null,
        public ?UuidV7 $moduleId = null,
        public ?array $beforeValues = null,
        public ?array $afterValues = null,
        public array $metadata = [],
        public ?string $ipAddress = null,
        public ?string $sessionId = null,
        public ?DateTimeImmutable $occurredAt = null,
    ) {}
}
