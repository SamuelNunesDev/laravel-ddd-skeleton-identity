<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\ValueObjects;

enum AuditSensitivity: string
{
    case Sensitive = 'sensitive';
    case NonSensitive = 'non_sensitive';
}
