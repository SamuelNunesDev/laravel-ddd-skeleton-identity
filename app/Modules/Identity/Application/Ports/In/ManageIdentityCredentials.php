<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Ports\In;

use App\Modules\Identity\Application\DTO\ChangeTemporaryPasswordData;
use App\Modules\Identity\Application\DTO\CredentialVerification;
use App\Modules\Identity\Application\DTO\IdentityDetails;
use App\Modules\Identity\Application\DTO\ResetTemporaryPasswordData;

interface ManageIdentityCredentials
{
    public function resetTemporaryPassword(ResetTemporaryPasswordData $data): IdentityDetails;

    public function changeTemporaryPassword(ChangeTemporaryPasswordData $data): IdentityDetails;

    public function verify(string $email, string $password): CredentialVerification;
}
