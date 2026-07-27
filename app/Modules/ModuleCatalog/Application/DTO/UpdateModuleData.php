<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Application\DTO;

use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Shared\Domain\ValueObjects\CorrelationContext;
use App\Shared\Domain\ValueObjects\UuidV7;

final readonly class UpdateModuleData
{
    /**
     * @param  list<string>  $audiences
     * @param  list<string>  $allowedScopes
     */
    public function __construct(
        public UuidV7 $moduleId,
        public string $name,
        public string $description,
        public array $audiences,
        public array $allowedScopes,
        public AuditActor $actor,
        public CorrelationContext $correlation,
    ) {}
}
