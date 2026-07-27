<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class EmailAddress implements Stringable
{
    public string $value;

    public string $normalized;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        $normalized = mb_strtolower($trimmed, 'UTF-8');

        if ($trimmed === '' || strlen($trimmed) > 254 || filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('A valid e-mail address is required.');
        }

        $this->value = $trimmed;
        $this->normalized = $normalized;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
