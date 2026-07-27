<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\Ports\Out;

use App\Modules\Organization\Domain\Entities\Organization;
use App\Modules\Organization\Domain\ValueObjects\OrganizationIdentifier;
use App\Shared\Domain\ValueObjects\UuidV7;

interface OrganizationStore
{
    public function findById(UuidV7 $id, bool $forUpdate = false): ?Organization;

    public function identifierExists(OrganizationIdentifier $identifier): bool;

    public function insert(Organization $organization): void;

    public function update(Organization $organization): void;

    /**
     * @param  list<UuidV7>  $ids
     * @return list<Organization>
     */
    public function findOperationalByIds(array $ids): array;
}
