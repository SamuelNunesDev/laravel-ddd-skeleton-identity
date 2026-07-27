<?php

declare(strict_types=1);

namespace App\Modules\Organization\Infrastructure\Integrations;

use App\Modules\Identity\Application\Ports\In\IdentityDirectory;
use App\Modules\Identity\Domain\Exceptions\IdentityNotFound;
use App\Modules\Organization\Application\DTO\IdentityAccessDetails;
use App\Modules\Organization\Application\Ports\Out\IdentityAccess;
use App\Shared\Domain\ValueObjects\UuidV7;

final readonly class IdentityAccessAdapter implements IdentityAccess
{
    public function __construct(private IdentityDirectory $identities) {}

    public function get(UuidV7 $identityId): ?IdentityAccessDetails
    {
        try {
            $identity = $this->identities->get($identityId);
        } catch (IdentityNotFound) {
            return null;
        }

        return new IdentityAccessDetails(
            active: $identity->status === 'active' && $identity->deletedAt === null,
            authorizationVersion: $identity->authorizationVersion,
        );
    }
}
