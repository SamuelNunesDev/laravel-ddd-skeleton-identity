<?php

declare(strict_types=1);

namespace App\Modules\Installation\Domain\ValueObjects;

use DateTimeZone;
use InvalidArgumentException;

final readonly class InstallationSettings
{
    public function __construct(
        public string $displayName,
        public ?string $shortName,
        public ?string $legalName,
        public ?string $institutionalDescription,
        public ?string $logoUrl,
        public ?string $logoDarkUrl,
        public ?string $faviconUrl,
        public ?string $primaryColor,
        public ?string $secondaryColor,
        public ?string $accentColor,
        public string $locale,
        public string $timezone,
        public ?string $senderName,
        public ?string $senderEmail,
        public ?string $supportEmail,
        public ?string $supportUrl,
        public ?string $termsUrl,
        public ?string $privacyPolicyUrl,
    ) {
        $this->assertLength($this->displayName, 1, 120, 'Display name');
        $this->assertOptionalLength($this->shortName, 60, 'Short name');
        $this->assertOptionalLength($this->legalName, 180, 'Legal name');
        $this->assertOptionalLength($this->institutionalDescription, 2000, 'Institutional description');
        $this->assertUrl($this->logoUrl, 'Logo URL');
        $this->assertUrl($this->logoDarkUrl, 'Dark logo URL');
        $this->assertUrl($this->faviconUrl, 'Favicon URL');
        $this->assertColor($this->primaryColor, 'Primary color');
        $this->assertColor($this->secondaryColor, 'Secondary color');
        $this->assertColor($this->accentColor, 'Accent color');

        if (preg_match('/^[a-z]{2,3}(?:_[A-Z]{2})?$/D', $this->locale) !== 1) {
            throw new InvalidArgumentException('Installation locale is invalid.');
        }

        if (! in_array($this->timezone, DateTimeZone::listIdentifiers(), true)) {
            throw new InvalidArgumentException('Installation time zone is invalid.');
        }

        $this->assertOptionalLength($this->senderName, 120, 'Sender name');
        $this->assertEmail($this->senderEmail, 'Sender e-mail');
        $this->assertEmail($this->supportEmail, 'Support e-mail');
        $this->assertUrl($this->supportUrl, 'Support URL');
        $this->assertUrl($this->termsUrl, 'Terms URL');
        $this->assertUrl($this->privacyPolicyUrl, 'Privacy policy URL');
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'display_name' => $this->displayName,
            'short_name' => $this->shortName,
            'legal_name' => $this->legalName,
            'institutional_description' => $this->institutionalDescription,
            'logo_url' => $this->logoUrl,
            'logo_dark_url' => $this->logoDarkUrl,
            'favicon_url' => $this->faviconUrl,
            'primary_color' => $this->primaryColor,
            'secondary_color' => $this->secondaryColor,
            'accent_color' => $this->accentColor,
            'locale' => $this->locale,
            'timezone' => $this->timezone,
            'sender_name' => $this->senderName,
            'sender_email' => $this->senderEmail,
            'support_email' => $this->supportEmail,
            'support_url' => $this->supportUrl,
            'terms_url' => $this->termsUrl,
            'privacy_policy_url' => $this->privacyPolicyUrl,
        ];
    }

    private function assertLength(string $value, int $minimum, int $maximum, string $label): void
    {
        $length = mb_strlen(trim($value), 'UTF-8');

        if ($length < $minimum || $length > $maximum) {
            throw new InvalidArgumentException(sprintf('%s length is invalid.', $label));
        }
    }

    private function assertOptionalLength(?string $value, int $maximum, string $label): void
    {
        if ($value !== null) {
            $this->assertLength($value, 1, $maximum, $label);
        }
    }

    private function assertUrl(?string $value, string $label): void
    {
        if ($value !== null && (strlen($value) > 2048 || filter_var($value, FILTER_VALIDATE_URL) === false)) {
            throw new InvalidArgumentException(sprintf('%s is invalid.', $label));
        }
    }

    private function assertColor(?string $value, string $label): void
    {
        if ($value !== null && preg_match('/^#[0-9a-fA-F]{6}$/D', $value) !== 1) {
            throw new InvalidArgumentException(sprintf('%s must use #RRGGBB.', $label));
        }
    }

    private function assertEmail(?string $value, string $label): void
    {
        if ($value !== null
            && (strlen($value) > 254 || filter_var($value, FILTER_VALIDATE_EMAIL) === false)) {
            throw new InvalidArgumentException(sprintf('%s is invalid.', $label));
        }
    }
}
