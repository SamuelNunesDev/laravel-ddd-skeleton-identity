<?php

declare(strict_types=1);

namespace Tests\Unit\Audit;

use App\Modules\Audit\Domain\Services\SensitiveDataRedactor;
use PHPUnit\Framework\TestCase;

final class SensitiveDataRedactorTest extends TestCase
{
    public function test_it_recursively_redacts_all_required_secret_categories(): void
    {
        $redacted = (new SensitiveDataRedactor)->redact([
            'email' => 'person@example.test',
            'password' => 'plain-password',
            'credentials' => [
                'access_token' => 'access-token',
                'clientSecret' => 'client-secret',
                'totp_code' => '123456',
                'recovery_codes' => ['one', 'two'],
            ],
            'safe' => ['result' => 'succeeded'],
        ]);

        self::assertSame('person@example.test', $redacted['email']);
        self::assertSame(SensitiveDataRedactor::REDACTED, $redacted['password']);
        self::assertSame(SensitiveDataRedactor::REDACTED, $redacted['credentials']['access_token']);
        self::assertSame(SensitiveDataRedactor::REDACTED, $redacted['credentials']['clientSecret']);
        self::assertSame(SensitiveDataRedactor::REDACTED, $redacted['credentials']['totp_code']);
        self::assertSame(SensitiveDataRedactor::REDACTED, $redacted['credentials']['recovery_codes']);
        self::assertSame(['result' => 'succeeded'], $redacted['safe']);
    }
}
