<?php

declare(strict_types=1);

namespace App\Modules\Installation\Application\DTO;

use DateTimeImmutable;

final readonly class OwnershipTransferProof
{
    public function __construct(
        public DateTimeImmutable $passwordReauthenticatedAt,
        public DateTimeImmutable $mfaVerifiedAt,
    ) {}
}
