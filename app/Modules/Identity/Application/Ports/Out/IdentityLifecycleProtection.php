<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Ports\Out;

use App\Shared\Domain\ValueObjects\UuidV7;

interface IdentityLifecycleProtection
{
    public function isProtectedOwner(UuidV7 $identityId): bool;
}
