<?php

declare(strict_types=1);

namespace App\Shared\Application\Contracts;

use App\Shared\Domain\ValueObjects\UuidV7;

interface UuidGenerator
{
    public function generate(): UuidV7;
}
