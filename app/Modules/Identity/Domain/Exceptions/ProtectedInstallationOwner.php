<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use RuntimeException;

final class ProtectedInstallationOwner extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The installation owner must be transferred before this lifecycle change.');
    }
}
