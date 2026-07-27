<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class TraceId implements Stringable
{
    private const PATTERN = '/^[0-9a-f]{32}$/D';

    private const ZERO = '00000000000000000000000000000000';

    private function __construct(private string $value) {}

    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(16)));
    }

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        if (preg_match(self::PATTERN, $normalized) !== 1 || $normalized === self::ZERO) {
            throw new InvalidArgumentException('The value must be a non-zero W3C trace ID.');
        }

        return new self($normalized);
    }

    public static function tryFromString(string $value): ?self
    {
        try {
            return self::fromString($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
