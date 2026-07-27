<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\Ports\In;

use App\Modules\Organization\Application\DTO\CreateMembershipData;
use App\Modules\Organization\Application\DTO\EndMembershipData;
use App\Modules\Organization\Application\DTO\MembershipDetails;
use App\Modules\Organization\Domain\ValueObjects\OrganizationContext;

interface ManageMemberships
{
    public function add(CreateMembershipData $data): MembershipDetails;

    public function end(EndMembershipData $data): MembershipDetails;

    /**
     * @return list<MembershipDetails>
     */
    public function forOrganization(OrganizationContext $context): array;
}
