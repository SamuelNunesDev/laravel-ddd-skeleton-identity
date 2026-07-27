<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObjects;

enum IdentityStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
}
