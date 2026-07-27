<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\ValueObjects;

enum AuditOutcome: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Denied = 'denied';
}
