<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\DTO;

use App\Modules\Organization\Domain\Entities\Organization;
use DateTimeImmutable;

final readonly class OrganizationDetails
{
    public function __construct(
        public string $id,
        public string $identifier,
        public string $name,
        public string $mfaPolicy,
        public string $status,
        public ?DateTimeImmutable $disabledAt,
        public ?DateTimeImmutable $deletedAt,
    ) {}

    public static function fromOrganization(Organization $organization): self
    {
        return new self(
            id: $organization->id()->toString(),
            identifier: $organization->identifier()->value,
            name: $organization->name(),
            mfaPolicy: $organization->mfaPolicy()->value,
            status: $organization->status()->value,
            disabledAt: $organization->disabledAt(),
            deletedAt: $organization->deletedAt(),
        );
    }
}
