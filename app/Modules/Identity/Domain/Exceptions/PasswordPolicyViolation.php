<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use InvalidArgumentException;

final class PasswordPolicyViolation extends InvalidArgumentException {}
