<?php

declare(strict_types=1);

namespace App\Modules\Installation\Domain\Exceptions;

use RuntimeException;

final class InstallationNotFound extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Installation has not been initialized.');
    }
}
