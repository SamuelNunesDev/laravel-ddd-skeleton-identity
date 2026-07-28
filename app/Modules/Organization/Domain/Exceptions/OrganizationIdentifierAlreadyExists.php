<?php

declare(strict_types=1);

namespace App\Modules\Organization\Domain\Exceptions;

use RuntimeException;
use Throwable;

final class OrganizationIdentifierAlreadyExists extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Organization identifier already exists.', previous: $previous);
    }
}
