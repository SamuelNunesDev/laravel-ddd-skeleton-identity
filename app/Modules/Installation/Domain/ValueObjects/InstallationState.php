<?php

declare(strict_types=1);

namespace App\Modules\Installation\Domain\ValueObjects;

enum InstallationState: string
{
    case PendingMfa = 'pending_mfa';
    case Active = 'active';
}
