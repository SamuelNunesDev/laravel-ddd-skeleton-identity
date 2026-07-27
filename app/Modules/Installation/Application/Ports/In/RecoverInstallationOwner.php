<?php

declare(strict_types=1);

namespace App\Modules\Installation\Application\Ports\In;

use App\Modules\Identity\Application\DTO\IdentityDetails;
use App\Modules\Installation\Application\DTO\RecoverOwnerData;

interface RecoverInstallationOwner
{
    public function recover(RecoverOwnerData $data): IdentityDetails;
}
