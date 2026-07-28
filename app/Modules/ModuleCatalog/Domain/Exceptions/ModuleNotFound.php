<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Domain\Exceptions;

use RuntimeException;

final class ModuleNotFound extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Module was not found.');
    }
}
