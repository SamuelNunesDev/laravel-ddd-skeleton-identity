<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class AllowedScope
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if ($normalized === '*'
            || preg_match('/^[a-z][a-z0-9._:-]{0,127}$/D', $normalized) !== 1) {
            throw new InvalidArgumentException('Allowed scope must be a stable lowercase identifier and cannot be a wildcard.');
        }

        $this->value = $normalized;
    }
}
