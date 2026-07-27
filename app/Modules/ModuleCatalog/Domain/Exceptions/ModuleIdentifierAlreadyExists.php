<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Domain\Exceptions;

use RuntimeException;
use Throwable;

final class ModuleIdentifierAlreadyExists extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Module identifier already exists.', previous: $previous);
    }
}
