<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\Ports\In;

use App\Modules\Organization\Application\DTO\CreateOrganizationData;
use App\Modules\Organization\Application\DTO\OrganizationDetails;
use App\Modules\Organization\Application\DTO\OrganizationLifecycleData;
use App\Modules\Organization\Application\DTO\UpdateOrganizationData;
use App\Shared\Domain\ValueObjects\UuidV7;

interface ManageOrganizations
{
    public function create(CreateOrganizationData $data): OrganizationDetails;

    public function get(UuidV7 $organizationId): OrganizationDetails;

    public function update(UpdateOrganizationData $data): OrganizationDetails;

    public function deactivate(OrganizationLifecycleData $data): OrganizationDetails;

    public function reactivate(OrganizationLifecycleData $data): OrganizationDetails;

    public function softDelete(OrganizationLifecycleData $data): OrganizationDetails;

    public function restore(OrganizationLifecycleData $data): OrganizationDetails;
}
