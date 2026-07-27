<?php

declare(strict_types=1);

namespace App\Modules\Installation\Application\DTO;

use App\Shared\Domain\ValueObjects\CorrelationContext;

final readonly class RecoverOwnerData
{
    public function __construct(
        public string $temporaryPassword,
        public CorrelationContext $correlation,
        public string $reason = 'server_admin_recovery',
    ) {}
}
