<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\Ports\Out;

use App\Modules\Organization\Application\DTO\IdentityAccessDetails;
use App\Shared\Domain\ValueObjects\UuidV7;

interface IdentityAccess
{
    public function get(UuidV7 $identityId): ?IdentityAccessDetails;
}
