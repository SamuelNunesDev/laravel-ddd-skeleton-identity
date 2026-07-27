<?php

declare(strict_types=1);

namespace App\Modules\Installation\Domain\Exceptions;

use RuntimeException;

final class InstallationOwnerRequired extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Only the installation owner may perform this operation.');
    }
}
