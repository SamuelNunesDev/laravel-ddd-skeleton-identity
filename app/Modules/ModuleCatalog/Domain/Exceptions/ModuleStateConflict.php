<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Domain\Exceptions;

use RuntimeException;

final class ModuleStateConflict extends RuntimeException {}
