<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Identifiers;

use App\Shared\Application\Contracts\Clock;
use App\Shared\Application\Contracts\UuidGenerator;
use App\Shared\Domain\ValueObjects\UuidV7;
use Illuminate\Support\Str;

final readonly class LaravelUuidV7Generator implements UuidGenerator
{
    public function __construct(private Clock $clock) {}

    public function generate(): UuidV7
    {
        return UuidV7::fromString(Str::uuid7($this->clock->now())->toString());
    }
}
