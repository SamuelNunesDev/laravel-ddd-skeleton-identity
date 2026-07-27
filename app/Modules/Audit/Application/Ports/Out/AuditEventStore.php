<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Ports\Out;

use App\Modules\Audit\Domain\Entities\AuditEvent;

interface AuditEventStore
{
    public function append(AuditEvent $event): void;
}
