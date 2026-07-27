<?php

declare(strict_types=1);

namespace Tests\Unit\Identity;

use App\Modules\Identity\Domain\Entities\Identity;
use App\Modules\Identity\Domain\Exceptions\IdentityStateConflict;
use App\Modules\Identity\Domain\ValueObjects\EmailAddress;
use App\Modules\Identity\Domain\ValueObjects\IdentityStatus;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class IdentityTest extends TestCase
{
    public function test_restoration_is_safe_and_does_not_reactivate_identity(): void
    {
        $now = new DateTimeImmutable('2026-07-27T12:00:00+00:00');
        $identity = Identity::create(
            UuidV7::fromString('018f47a2-4b9d-7cc1-8b7a-112233445566'),
            new EmailAddress('person@example.test'),
            'Person',
            $now,
        );

        $identity->softDelete($now->modify('+1 minute'));
        $identity->restore($now->modify('+2 minutes'));

        self::assertSame(IdentityStatus::Disabled, $identity->status());
        self::assertFalse($identity->canAuthenticate());
        self::assertNull($identity->deletedAt());
        self::assertSame(3, $identity->authorizationVersion());
    }

    public function test_soft_deleted_identity_cannot_be_updated_before_restoration(): void
    {
        $now = new DateTimeImmutable('2026-07-27T12:00:00+00:00');
        $identity = Identity::create(
            UuidV7::fromString('018f47a2-4b9d-7cc1-8b7a-112233445566'),
            new EmailAddress('person@example.test'),
            'Person',
            $now,
        );
        $identity->softDelete($now);

        $this->expectException(IdentityStateConflict::class);

        $identity->updateProfile(new EmailAddress('new@example.test'), 'New Person', $now);
    }
}
