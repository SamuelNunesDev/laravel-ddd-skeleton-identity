<?php

declare(strict_types=1);

namespace App\Modules\Installation\Infrastructure\Integrations;

use App\Modules\Identity\Application\Ports\Out\IdentityLifecycleProtection;
use App\Shared\Domain\ValueObjects\UuidV7;
use Illuminate\Database\DatabaseManager;

final readonly class InstallationOwnerProtectionAdapter implements IdentityLifecycleProtection
{
    public function __construct(private DatabaseManager $database) {}

    public function isProtectedOwner(UuidV7 $identityId): bool
    {
        return $this->database->connection()
            ->table('installations')
            ->where('owner_identity_id', $identityId->toString())
            ->exists();
    }
}
