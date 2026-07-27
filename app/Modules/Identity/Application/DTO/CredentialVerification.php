<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTO;

use App\Shared\Domain\ValueObjects\UuidV7;

final readonly class CredentialVerification
{
    private function __construct(
        public bool $valid,
        public ?UuidV7 $identityId,
        public bool $mustChangePassword,
    ) {}

    public static function valid(UuidV7 $identityId, bool $mustChangePassword): self
    {
        return new self(true, $identityId, $mustChangePassword);
    }

    public static function invalid(): self
    {
        return new self(false, null, false);
    }
}
