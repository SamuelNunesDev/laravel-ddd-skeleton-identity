<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Domain\ValueObjects;

enum ModuleStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
}
