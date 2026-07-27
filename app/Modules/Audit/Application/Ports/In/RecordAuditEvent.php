<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Ports\In;

use App\Modules\Audit\Application\DTO\AuditEventData;
use App\Shared\Domain\ValueObjects\UuidV7;

interface RecordAuditEvent
{
    public function record(AuditEventData $data): UuidV7;
}
