<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTO;

use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Shared\Domain\ValueObjects\CorrelationContext;

final readonly class CreateIdentityData
{
    public function __construct(
        public string $email,
        public string $displayName,
        public string $temporaryPassword,
        public AuditActor $actor,
        public CorrelationContext $correlation,
        public ?int $temporaryPasswordHours = null,
    ) {}
}
