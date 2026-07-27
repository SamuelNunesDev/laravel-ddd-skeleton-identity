<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Ports\In;

use App\Modules\Identity\Application\DTO\CreateIdentityData;
use App\Modules\Identity\Application\DTO\IdentityDetails;

interface CreateIdentity
{
    public function create(CreateIdentityData $data): IdentityDetails;
}
