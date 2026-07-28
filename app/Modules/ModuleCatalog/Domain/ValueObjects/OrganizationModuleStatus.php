<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Domain\ValueObjects;

enum OrganizationModuleStatus: string
{
    case Enabled = 'enabled';
    case Disabled = 'disabled';
}
