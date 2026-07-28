<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\DTO;

use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Modules\Organization\Domain\ValueObjects\MfaPolicy;
use App\Shared\Domain\ValueObjects\CorrelationContext;

final readonly class CreateOrganizationData
{
    public function __construct(
        public string $identifier,
        public string $name,
        public MfaPolicy $mfaPolicy,
        public AuditActor $actor,
        public CorrelationContext $correlation,
    ) {}
}
