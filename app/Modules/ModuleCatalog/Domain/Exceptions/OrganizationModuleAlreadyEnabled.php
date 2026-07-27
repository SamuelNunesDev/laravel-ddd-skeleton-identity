<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Domain\Exceptions;

use RuntimeException;
use Throwable;

final class OrganizationModuleAlreadyEnabled extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Module is already enabled for the organization.', previous: $previous);
    }
}
