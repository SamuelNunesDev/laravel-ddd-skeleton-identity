<?php

declare(strict_types=1);

namespace App\Modules\Organization\Domain\ValueObjects;

use App\Shared\Domain\ValueObjects\UuidV7;
use InvalidArgumentException;

final readonly class OrganizationContext
{
    public function __construct(
        public UuidV7 $identityId,
        public UuidV7 $organizationId,
        public ?UuidV7 $moduleId,
        public OrganizationContextSource $source,
        public int $authorizationVersion,
    ) {
        if ($this->authorizationVersion < 1) {
            throw new InvalidArgumentException('Organization context authorization version must be positive.');
        }
    }
}
