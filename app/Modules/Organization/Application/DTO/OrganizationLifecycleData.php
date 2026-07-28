<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\DTO;

use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Shared\Domain\ValueObjects\CorrelationContext;
use App\Shared\Domain\ValueObjects\UuidV7;

final readonly class OrganizationLifecycleData
{
    public function __construct(
        public UuidV7 $organizationId,
        public AuditActor $actor,
        public CorrelationContext $correlation,
        public ?string $reason = null,
    ) {}
}
