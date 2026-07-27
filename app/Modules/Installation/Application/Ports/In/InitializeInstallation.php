<?php

declare(strict_types=1);

namespace App\Modules\Installation\Application\Ports\In;

use App\Modules\Installation\Application\DTO\InitializeInstallationData;
use App\Modules\Installation\Application\DTO\InstallationDetails;

interface InitializeInstallation
{
    public function initialize(InitializeInstallationData $data): InstallationDetails;
}
