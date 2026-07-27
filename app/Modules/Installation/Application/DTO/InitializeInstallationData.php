<?php

declare(strict_types=1);

namespace App\Modules\Installation\Application\DTO;

use App\Shared\Domain\ValueObjects\CorrelationContext;

final readonly class InitializeInstallationData
{
    public function __construct(
        public string $ownerEmail,
        public string $ownerDisplayName,
        public string $temporaryPassword,
        public CorrelationContext $correlation,
    ) {}
}
