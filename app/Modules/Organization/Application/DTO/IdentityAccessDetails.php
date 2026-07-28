<?php

declare(strict_types=1);

namespace App\Modules\Organization\Application\DTO;

final readonly class IdentityAccessDetails
{
    public function __construct(
        public bool $active,
        public int $authorizationVersion,
    ) {}
}
