<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Ports\In;

use App\Modules\Identity\Application\DTO\IdentityDetails;
use App\Modules\Identity\Application\DTO\IdentityLifecycleData;

interface ManageIdentityLifecycle
{
    public function deactivate(IdentityLifecycleData $data): IdentityDetails;

    public function reactivate(IdentityLifecycleData $data): IdentityDetails;

    public function softDelete(IdentityLifecycleData $data): IdentityDetails;

    public function restore(IdentityLifecycleData $data): IdentityDetails;
}
