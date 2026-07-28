<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Domain\Exceptions;

use RuntimeException;

final class OrganizationContextMismatch extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Organization context does not match this catalog operation.');
    }
}
