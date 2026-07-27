<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use RuntimeException;
use Throwable;

final class IdentityEmailAlreadyExists extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('An active identity already uses this e-mail address.', previous: $previous);
    }
}
