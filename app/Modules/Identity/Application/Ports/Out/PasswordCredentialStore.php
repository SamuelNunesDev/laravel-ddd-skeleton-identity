<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Ports\Out;

use App\Modules\Identity\Domain\Entities\PasswordCredential;
use App\Shared\Domain\ValueObjects\UuidV7;

interface PasswordCredentialStore
{
    public function findByIdentityId(UuidV7 $identityId, bool $forUpdate = false): ?PasswordCredential;

    public function save(PasswordCredential $credential): void;
}
