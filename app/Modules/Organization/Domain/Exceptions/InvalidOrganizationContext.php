<?php

declare(strict_types=1);

namespace App\Modules\Organization\Domain\Exceptions;

use RuntimeException;

final class InvalidOrganizationContext extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Identity, organization, membership, or module context is not active.');
    }
}
