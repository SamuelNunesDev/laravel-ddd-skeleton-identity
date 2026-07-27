<?php

declare(strict_types=1);

namespace App\Modules\Organization\Domain\Exceptions;

use RuntimeException;

final class MembershipNotFound extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Membership was not found.');
    }
}
