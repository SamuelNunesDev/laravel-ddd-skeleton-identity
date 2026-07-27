<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Domain\ValueObjects\UuidV7;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UuidV7Test extends TestCase
{
    public function test_it_accepts_and_normalizes_a_canonical_uuid_v7(): void
    {
        $uuid = UuidV7::fromString('018F47A2-4B9D-7CC1-8B7A-112233445566');

        self::assertSame('018f47a2-4b9d-7cc1-8b7a-112233445566', $uuid->toString());
    }

    #[DataProvider('invalidUuidProvider')]
    public function test_it_rejects_values_that_are_not_uuid_v7(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        UuidV7::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidUuidProvider(): iterable
    {
        yield 'uuid v4' => ['550e8400-e29b-41d4-a716-446655440000'];
        yield 'invalid variant' => ['018f47a2-4b9d-7cc1-7b7a-112233445566'];
        yield 'arbitrary input' => ['not-a-uuid'];
    }
}
