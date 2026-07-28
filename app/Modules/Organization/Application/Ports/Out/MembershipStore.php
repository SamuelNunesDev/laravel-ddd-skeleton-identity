<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\Ports\Out;

use App\Modules\Organization\Domain\Entities\Membership;
use App\Shared\Domain\ValueObjects\UuidV7;

interface MembershipStore
{
    public function findById(UuidV7 $id, bool $forUpdate = false): ?Membership;

    public function findActive(UuidV7 $identityId, UuidV7 $organizationId, bool $forUpdate = false): ?Membership;

    public function insert(Membership $membership): void;

    public function update(Membership $membership): void;

    /**
     * @return list<UuidV7>
     */
    public function activeOrganizationIdsFor(UuidV7 $identityId): array;

    /**
     * @return list<Membership>
     */
    public function forOrganization(UuidV7 $organizationId): array;
}
