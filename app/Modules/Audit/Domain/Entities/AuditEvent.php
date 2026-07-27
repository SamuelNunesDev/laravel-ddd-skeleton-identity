<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Entities;

use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Modules\Audit\Domain\ValueObjects\AuditOutcome;
use App\Modules\Audit\Domain\ValueObjects\AuditSensitivity;
use App\Modules\Audit\Domain\ValueObjects\AuditTarget;
use App\Shared\Domain\ValueObjects\CorrelationContext;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class AuditEvent
{
    /**
     * @param  array<array-key, mixed>|null  $beforeValues
     * @param  array<array-key, mixed>|null  $afterValues
     * @param  array<array-key, mixed>  $metadata
     */
    public function __construct(
        public UuidV7 $id,
        public string $action,
        public AuditActor $actor,
        public AuditOutcome $outcome,
        public AuditSensitivity $sensitivity,
        public CorrelationContext $correlation,
        public DateTimeImmutable $occurredAt,
        public ?AuditTarget $target = null,
        public ?UuidV7 $organizationId = null,
        public ?UuidV7 $moduleId = null,
        public ?array $beforeValues = null,
        public ?array $afterValues = null,
        public array $metadata = [],
        public ?string $ipAddress = null,
        public ?string $sessionId = null,
    ) {
        if (preg_match('/^[a-z][a-z0-9._-]{2,254}$/D', $this->action) !== 1) {
            throw new InvalidArgumentException('Audit action must be a stable lowercase identifier.');
        }

        if ($this->ipAddress !== null && filter_var($this->ipAddress, FILTER_VALIDATE_IP) === false) {
            throw new InvalidArgumentException('Audit IP address is invalid.');
        }

        if ($this->sessionId !== null && (trim($this->sessionId) === '' || strlen($this->sessionId) > 255)) {
            throw new InvalidArgumentException('Audit session ID must contain between 1 and 255 bytes.');
        }

        $this->assertJsonEncodable($this->beforeValues);
        $this->assertJsonEncodable($this->afterValues);
        $this->assertJsonEncodable($this->metadata);
    }

    /**
     * @param  array<array-key, mixed>|null  $values
     */
    private function assertJsonEncodable(?array $values): void
    {
        if ($values === null) {
            return;
        }

        json_encode($values, JSON_THROW_ON_ERROR);
    }
}
