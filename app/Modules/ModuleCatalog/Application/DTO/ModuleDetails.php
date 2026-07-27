<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Application\DTO;

use App\Modules\ModuleCatalog\Domain\Entities\ModuleDefinition;
use DateTimeImmutable;

final readonly class ModuleDetails
{
    /**
     * @param  list<string>  $audiences
     * @param  list<string>  $allowedScopes
     */
    public function __construct(
        public string $id,
        public string $identifier,
        public string $name,
        public string $description,
        public string $status,
        public array $audiences,
        public array $allowedScopes,
        public ?DateTimeImmutable $disabledAt,
        public ?DateTimeImmutable $deletedAt,
    ) {}

    /**
     * @param  list<string>  $audiences
     * @param  list<string>  $allowedScopes
     */
    public static function fromModule(
        ModuleDefinition $module,
        array $audiences,
        array $allowedScopes,
    ): self {
        return new self(
            id: $module->id()->toString(),
            identifier: $module->identifier()->value,
            name: $module->name(),
            description: $module->description(),
            status: $module->status()->value,
            audiences: $audiences,
            allowedScopes: $allowedScopes,
            disabledAt: $module->disabledAt(),
            deletedAt: $module->deletedAt(),
        );
    }
}
