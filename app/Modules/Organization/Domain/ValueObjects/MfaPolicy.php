<?php

declare(strict_types=1);

namespace App\Modules\Organization\Domain\ValueObjects;

enum MfaPolicy: string
{
    case Required = 'required';
    case Optional = 'optional';
}
