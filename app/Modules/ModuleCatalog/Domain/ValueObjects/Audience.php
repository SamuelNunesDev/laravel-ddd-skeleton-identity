<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Audience
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:\\/-]{0,190}$/D', $normalized) !== 1) {
            throw new InvalidArgumentException('Audience must be a stable identifier or URI without whitespace.');
        }

        $this->value = $normalized;
    }
}
