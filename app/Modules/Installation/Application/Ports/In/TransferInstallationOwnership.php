<?php

declare(strict_types=1);

namespace App\Modules\Installation\Application\Ports\In;

use App\Modules\Installation\Application\DTO\InstallationDetails;
use App\Modules\Installation\Application\DTO\OwnershipTransferProof;
use App\Shared\Domain\ValueObjects\UuidV7;

/**
 * Executable implementation is intentionally deferred to M6, where password
 * reauthentication and MFA step-up can be verified.
 */
interface TransferInstallationOwnership
{
    public function transfer(UuidV7 $newOwnerIdentityId, OwnershipTransferProof $proof): InstallationDetails;
}
