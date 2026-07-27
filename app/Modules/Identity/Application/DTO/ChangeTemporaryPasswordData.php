<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTO;

use App\Shared\Domain\ValueObjects\CorrelationContext;
use App\Shared\Domain\ValueObjects\UuidV7;

final readonly class ChangeTemporaryPasswordData
{
    public function __construct(
        public UuidV7 $identityId,
        public string $temporaryPassword,
        public string $newPassword,
        public CorrelationContext $correlation,
    ) {}
}
