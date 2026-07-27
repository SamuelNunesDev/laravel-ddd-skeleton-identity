<?php

declare(strict_types=1);

namespace App\Modules\Organization\Domain\ValueObjects;

enum MembershipStatus: string
{
    case Active = 'active';
    case Ended = 'ended';
}
