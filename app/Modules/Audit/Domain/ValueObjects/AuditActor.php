<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\ValueObjects;

use App\Shared\Domain\ValueObjects\UuidV7;

final readonly class AuditActor
{
    private function __construct(
        public AuditActorType $type,
        public ?UuidV7 $id,
    ) {}

    public static function identity(UuidV7 $id): self
    {
        return new self(AuditActorType::Identity, $id);
    }

    public static function oauthClient(UuidV7 $id): self
    {
        return new self(AuditActorType::OAuthClient, $id);
    }

    public static function system(): self
    {
        return new self(AuditActorType::System, null);
    }
}
