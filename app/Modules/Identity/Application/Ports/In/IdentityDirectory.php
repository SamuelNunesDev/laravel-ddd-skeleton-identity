<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Ports\In;

use App\Modules\Identity\Application\DTO\IdentityDetails;
use App\Modules\Identity\Application\DTO\UpdateIdentityData;
use App\Shared\Domain\ValueObjects\UuidV7;

interface IdentityDirectory
{
    public function get(UuidV7 $identityId): IdentityDetails;

    public function update(UpdateIdentityData $data): IdentityDetails;
}
