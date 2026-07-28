<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\Ports\In;

use App\Modules\Organization\Application\DTO\AvailableModule;
use App\Modules\Organization\Application\DTO\OrganizationOption;
use App\Modules\Organization\Domain\ValueObjects\OrganizationContext;
use App\Modules\Organization\Domain\ValueObjects\OrganizationContextSource;
use App\Shared\Domain\ValueObjects\CorrelationContext;
use App\Shared\Domain\ValueObjects\UuidV7;

interface OrganizationSelection
{
    /**
     * @return list<OrganizationOption>
     */
    public function organizationsFor(UuidV7 $identityId, ?UuidV7 $moduleId = null): array;

    /**
     * @return list<AvailableModule>
     */
    public function modulesFor(OrganizationContext $context): array;

    public function preferred(
        UuidV7 $identityId,
        ?UuidV7 $moduleId,
        OrganizationContextSource $source,
    ): ?OrganizationContext;

    public function remember(OrganizationContext $context, CorrelationContext $correlation): void;
}
