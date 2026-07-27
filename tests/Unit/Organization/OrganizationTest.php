<?php

declare(strict_types=1);

namespace Tests\Unit\Organization;

use App\Modules\Organization\Domain\Entities\Organization;
use App\Modules\Organization\Domain\ValueObjects\MfaPolicy;
use App\Modules\Organization\Domain\ValueObjects\OrganizationIdentifier;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class OrganizationTest extends TestCase
{
    public function test_restore_is_explicit_and_does_not_reactivate_the_organization(): void
    {
        $now = new DateTimeImmutable('2026-07-27T12:00:00+00:00');
        $organization = Organization::create(
            id: UuidV7::fromString('019fa000-0000-7000-8000-000000000001'),
            identifier: new OrganizationIdentifier('acme'),
            name: 'Acme',
            mfaPolicy: MfaPolicy::Required,
            now: $now,
        );

        $organization->softDelete($now);
        $organization->restore($now->modify('+1 hour'));

        self::assertSame('disabled', $organization->status()->value);
        self::assertNull($organization->deletedAt());
        self::assertFalse($organization->isOperational());
    }
}
