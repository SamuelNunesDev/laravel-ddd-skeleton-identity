<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\ValueObjects;

use App\Shared\Domain\ValueObjects\UuidV7;
use InvalidArgumentException;

final readonly class AuditTarget
{
    public function __construct(
        public string $type,
        public UuidV7 $id,
    ) {
        if (preg_match('/^[a-z][a-z0-9_.-]{1,127}$/D', $this->type) !== 1) {
            throw new InvalidArgumentException('Audit target type must be a stable lowercase identifier.');
        }
    }
}
