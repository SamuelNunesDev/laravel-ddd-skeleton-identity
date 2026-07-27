<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Entities;

use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;

final readonly class PasswordCredential
{
    public function __construct(
        public UuidV7 $identityId,
        public string $passwordHash,
        public ?DateTimeImmutable $temporaryExpiresAt,
        public ?DateTimeImmutable $changedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    public function isTemporaryAndValidAt(DateTimeImmutable $now): bool
    {
        return $this->temporaryExpiresAt !== null && $this->temporaryExpiresAt > $now;
    }
}
