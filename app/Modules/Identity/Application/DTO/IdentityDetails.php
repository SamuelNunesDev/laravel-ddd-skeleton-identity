<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTO;

use App\Modules\Identity\Domain\Entities\Identity;
use DateTimeImmutable;

final readonly class IdentityDetails
{
    public function __construct(
        public string $id,
        public string $email,
        public string $displayName,
        public string $status,
        public bool $mustChangePassword,
        public int $authorizationVersion,
        public ?DateTimeImmutable $disabledAt,
        public ?DateTimeImmutable $deletedAt,
    ) {}

    public static function fromIdentity(Identity $identity): self
    {
        return new self(
            id: $identity->id()->toString(),
            email: $identity->email()->value,
            displayName: $identity->displayName(),
            status: $identity->status()->value,
            mustChangePassword: $identity->mustChangePassword(),
            authorizationVersion: $identity->authorizationVersion(),
            disabledAt: $identity->disabledAt(),
            deletedAt: $identity->deletedAt(),
        );
    }
}
