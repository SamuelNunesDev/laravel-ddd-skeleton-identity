<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTO;

use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Shared\Domain\ValueObjects\CorrelationContext;
use App\Shared\Domain\ValueObjects\UuidV7;

final readonly class IdentityLifecycleData
{
    public function __construct(
        public UuidV7 $identityId,
        public AuditActor $actor,
        public CorrelationContext $correlation,
        public ?string $reason = null,
    ) {}
}
