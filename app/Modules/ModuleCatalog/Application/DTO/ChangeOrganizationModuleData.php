<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Application\DTO;

use App\Modules\Organization\Domain\ValueObjects\OrganizationContext;
use App\Shared\Domain\ValueObjects\CorrelationContext;
use App\Shared\Domain\ValueObjects\UuidV7;

final readonly class ChangeOrganizationModuleData
{
    public function __construct(
        public OrganizationContext $context,
        public UuidV7 $moduleId,
        public CorrelationContext $correlation,
        public ?string $reason = null,
    ) {}
}
