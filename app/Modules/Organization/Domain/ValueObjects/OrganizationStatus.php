<?php

declare(strict_types=1);

namespace App\Modules\Organization\Domain\ValueObjects;

enum OrganizationStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
}
