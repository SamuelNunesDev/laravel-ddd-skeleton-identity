<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObjects;

use Stringable;

final readonly class RequestId implements Stringable
{
    public function __construct(private UuidV7 $value) {}

    public static function fromString(string $value): self
    {
        return new self(UuidV7::fromString($value));
    }

    public static function tryFromString(string $value): ?self
    {
        $uuid = UuidV7::tryFromString($value);

        return $uuid === null ? null : new self($uuid);
    }

    public function toUuid(): UuidV7
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value->toString();
    }
}
