<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\DTO;

use App\Modules\Organization\Domain\ValueObjects\OrganizationContextSource;
use App\Shared\Domain\ValueObjects\UuidV7;

final readonly class ResolveOrganizationContextData
{
    public function __construct(
        public UuidV7 $identityId,
        public UuidV7 $organizationId,
        public ?UuidV7 $moduleId,
        public OrganizationContextSource $source,
    ) {}
}
