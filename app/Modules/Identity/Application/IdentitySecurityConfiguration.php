<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application;

use App\Modules\Identity\Domain\Exceptions\PasswordPolicyViolation;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class IdentitySecurityConfiguration
{
    public function __construct(
        public int $minimumPasswordLength,
        public int $maximumPasswordBytes,
        public int $temporaryPasswordHours,
        public int $maximumTemporaryPasswordHours,
    ) {
        if ($this->minimumPasswordLength < 1 || $this->maximumPasswordBytes < $this->minimumPasswordLength) {
            throw new InvalidArgumentException('Identity password limits are invalid.');
        }

        if ($this->temporaryPasswordHours < 1
            || $this->maximumTemporaryPasswordHours > 72
            || $this->temporaryPasswordHours > $this->maximumTemporaryPasswordHours) {
            throw new InvalidArgumentException('Temporary password lifetime must be between 1 and 72 hours.');
        }
    }

    public function assertPassword(string $password): void
    {
        if (mb_strlen($password, 'UTF-8') < $this->minimumPasswordLength) {
            throw new PasswordPolicyViolation(
                sprintf('Password must contain at least %d characters.', $this->minimumPasswordLength),
            );
        }

        if (strlen($password) > $this->maximumPasswordBytes) {
            throw new PasswordPolicyViolation('Password exceeds the configured byte limit.');
        }
    }

    public function temporaryExpiration(DateTimeImmutable $now, ?int $requestedHours): DateTimeImmutable
    {
        $hours = $requestedHours ?? $this->temporaryPasswordHours;

        if ($hours < 1 || $hours > $this->maximumTemporaryPasswordHours) {
            throw new PasswordPolicyViolation(
                sprintf('Temporary password lifetime must be between 1 and %d hours.', $this->maximumTemporaryPasswordHours),
            );
        }

        return $now->add(new DateInterval(sprintf('PT%dH', $hours)));
    }
}
