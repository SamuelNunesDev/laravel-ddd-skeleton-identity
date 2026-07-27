<?php

declare(strict_types=1);

namespace App\Modules\Installation\Application;

final readonly class InstallationDefaults
{
    public function __construct(
        public string $displayName,
        public string $locale,
        public string $timezone,
    ) {}
}
