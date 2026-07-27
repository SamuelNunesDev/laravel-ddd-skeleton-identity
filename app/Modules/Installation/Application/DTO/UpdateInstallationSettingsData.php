<?php

declare(strict_types=1);

namespace App\Modules\Installation\Application\DTO;

use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Modules\Installation\Domain\ValueObjects\InstallationSettings;
use App\Shared\Domain\ValueObjects\CorrelationContext;

final readonly class UpdateInstallationSettingsData
{
    public function __construct(
        public InstallationSettings $settings,
        public AuditActor $actor,
        public CorrelationContext $correlation,
    ) {}
}
