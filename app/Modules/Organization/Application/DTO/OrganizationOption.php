<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\DTO;

final readonly class OrganizationOption
{
    public function __construct(
        public string $id,
        public string $identifier,
        public string $name,
        public string $mfaPolicy,
        public bool $preferred,
    ) {}
}
