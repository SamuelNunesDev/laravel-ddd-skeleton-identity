<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Application\DTO;

use App\Modules\ModuleCatalog\Domain\Entities\OrganizationModule;
use DateTimeImmutable;

final readonly class OrganizationModuleDetails
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $moduleId,
        public string $status,
        public DateTimeImmutable $enabledAt,
        public ?DateTimeImmutable $disabledAt,
    ) {}

    public static function fromOrganizationModule(OrganizationModule $organizationModule): self
    {
        return new self(
            id: $organizationModule->id()->toString(),
            organizationId: $organizationModule->organizationId()->toString(),
            moduleId: $organizationModule->moduleId()->toString(),
            status: $organizationModule->status()->value,
            enabledAt: $organizationModule->enabledAt(),
            disabledAt: $organizationModule->disabledAt(),
        );
    }
}
