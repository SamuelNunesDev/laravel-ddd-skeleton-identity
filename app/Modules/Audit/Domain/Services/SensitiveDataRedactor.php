<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Services;

final class SensitiveDataRedactor
{
    public const REDACTED = '[REDACTED]';

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    public function redact(array $values): array
    {
        $redacted = [];

        foreach ($values as $key => $value) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $redacted[$key] = self::REDACTED;

                continue;
            }

            $redacted[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $redacted;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $key));

        foreach (['password', 'passwd', 'passphrase', 'token', 'secret', 'totp', 'recoverycode', 'authorization'] as $sensitive) {
            if (str_contains($normalized, $sensitive)) {
                return true;
            }
        }

        return false;
    }
}
