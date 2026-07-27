<?php

declare(strict_types=1);

namespace App\Shared\Application\Integration;

use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class IntegrationEventMessage
{
    /**
     * @param  array<array-key, mixed>  $payload
     */
    public function __construct(
        public string $name,
        public string $aggregateType,
        public UuidV7 $aggregateId,
        public DateTimeImmutable $occurredAt,
        public array $payload,
    ) {
        if (preg_match('/^[a-z][a-z0-9._-]{2,190}\.v[1-9][0-9]*$/D', $this->name) !== 1) {
            throw new InvalidArgumentException('Integration event names must be stable and versioned.');
        }

        if (preg_match('/^[a-z][a-z0-9._-]{1,126}$/D', $this->aggregateType) !== 1) {
            throw new InvalidArgumentException('Integration aggregate type is invalid.');
        }

        json_encode($this->payload, JSON_THROW_ON_ERROR);
    }
}
