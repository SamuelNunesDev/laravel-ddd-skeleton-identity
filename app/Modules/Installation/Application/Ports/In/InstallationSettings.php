<?php

declare(strict_types=1);

namespace App\Modules\Installation\Application\Ports\In;

use App\Modules\Installation\Application\DTO\InstallationDetails;
use App\Modules\Installation\Application\DTO\UpdateInstallationSettingsData;

interface InstallationSettings
{
    public function get(): InstallationDetails;

    public function update(UpdateInstallationSettingsData $data): InstallationDetails;
}
