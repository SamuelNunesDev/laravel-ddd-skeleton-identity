<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class ModuleIdentifier
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower(trim($value));

        if (preg_match('/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D', $normalized) !== 1
            || strlen($normalized) > 63) {
            throw new InvalidArgumentException('Module identifier must be lowercase kebab-case.');
        }

        $this->value = $normalized;
    }
}
