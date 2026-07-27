<?php

declare(strict_types=1);

namespace Tests\Unit\Identity;

use App\Modules\Identity\Domain\ValueObjects\EmailAddress;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EmailAddressTest extends TestCase
{
    public function test_it_normalizes_email_deterministically(): void
    {
        $email = new EmailAddress('  Person@Example.TEST ');

        self::assertSame('Person@Example.TEST', $email->value);
        self::assertSame('person@example.test', $email->normalized);
    }

    public function test_it_rejects_invalid_email(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new EmailAddress('not-an-email');
    }
}
