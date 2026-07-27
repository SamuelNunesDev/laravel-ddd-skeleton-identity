<?php

declare(strict_types=1);

namespace App\Modules\Organization\Domain\Exceptions;

use RuntimeException;

final class MembershipStateConflict extends RuntimeException {}
