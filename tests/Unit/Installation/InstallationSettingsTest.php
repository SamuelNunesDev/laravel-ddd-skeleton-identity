<?php

declare(strict_types=1);

namespace Tests\Unit\Installation;

use App\Modules\Installation\Domain\ValueObjects\InstallationSettings;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class InstallationSettingsTest extends TestCase
{
    public function test_it_accepts_only_public_presentation_fields(): void
    {
        $settings = new InstallationSettings(
            displayName: 'Identity Platform',
            shortName: 'Identity',
            legalName: 'Example Ltd.',
            institutionalDescription: 'A public product description.',
            logoUrl: 'https://example.test/logo.svg',
            logoDarkUrl: null,
            faviconUrl: 'https://example.test/favicon.ico',
            primaryColor: '#112233',
            secondaryColor: '#AABBCC',
            accentColor: '#445566',
            locale: 'pt_BR',
            timezone: 'America/Sao_Paulo',
            senderName: 'Identity Platform',
            senderEmail: 'notifications@example.test',
            supportEmail: 'support@example.test',
            supportUrl: 'https://example.test/support',
            termsUrl: 'https://example.test/terms',
            privacyPolicyUrl: 'https://example.test/privacy',
        );

        self::assertArrayNotHasKey('secret', $settings->toArray());
        self::assertSame('#112233', $settings->toArray()['primary_color']);
        self::assertSame('#445566', $settings->toArray()['accent_color']);
        self::assertSame('notifications@example.test', $settings->toArray()['sender_email']);
    }

    public function test_it_rejects_invalid_color(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new InstallationSettings(
            displayName: 'Identity Platform',
            shortName: null,
            legalName: null,
            institutionalDescription: null,
            logoUrl: null,
            logoDarkUrl: null,
            faviconUrl: null,
            primaryColor: 'javascript:alert(1)',
            secondaryColor: null,
            accentColor: null,
            locale: 'pt_BR',
            timezone: 'UTC',
            senderName: null,
            senderEmail: null,
            supportEmail: null,
            supportUrl: null,
            termsUrl: null,
            privacyPolicyUrl: null,
        );
    }
}
