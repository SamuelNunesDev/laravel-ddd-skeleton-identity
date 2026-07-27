<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use RuntimeException;

final class TemporaryCredentialInvalid extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The temporary credential is invalid or expired.');
    }
}
