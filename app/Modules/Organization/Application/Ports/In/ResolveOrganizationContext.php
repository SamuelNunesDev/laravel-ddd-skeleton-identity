<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\Ports\In;

use App\Modules\Organization\Application\DTO\ResolveOrganizationContextData;
use App\Modules\Organization\Domain\ValueObjects\OrganizationContext;

interface ResolveOrganizationContext
{
    public function resolve(ResolveOrganizationContextData $data): OrganizationContext;
}
