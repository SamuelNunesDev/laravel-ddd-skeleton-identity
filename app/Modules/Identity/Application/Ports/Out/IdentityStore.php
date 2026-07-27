<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Ports\Out;

use App\Modules\Identity\Domain\Entities\Identity;
use App\Modules\Identity\Domain\ValueObjects\EmailAddress;
use App\Shared\Domain\ValueObjects\UuidV7;

interface IdentityStore
{
    public function findById(UuidV7 $id, bool $forUpdate = false): ?Identity;

    public function findActiveByEmail(EmailAddress $email): ?Identity;

    public function emailExistsForOtherActiveIdentity(EmailAddress $email, UuidV7 $identityId): bool;

    public function insert(Identity $identity): void;

    public function update(Identity $identity): void;
}
