<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Application\Ports\Out;

use App\Modules\ModuleCatalog\Domain\Entities\ModuleDefinition;
use App\Modules\ModuleCatalog\Domain\ValueObjects\AllowedScope;
use App\Modules\ModuleCatalog\Domain\ValueObjects\Audience;
use App\Modules\ModuleCatalog\Domain\ValueObjects\ModuleIdentifier;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;

interface ModuleStore
{
    public function findById(UuidV7 $id, bool $forUpdate = false): ?ModuleDefinition;

    public function identifierExists(ModuleIdentifier $identifier): bool;

    public function insert(ModuleDefinition $module): void;

    public function update(ModuleDefinition $module): void;

    /**
     * @param  list<Audience>  $audiences
     * @param  list<AllowedScope>  $allowedScopes
     */
    public function replaceProtocolMetadata(
        UuidV7 $moduleId,
        array $audiences,
        array $allowedScopes,
        DateTimeImmutable $now,
    ): void;

    /**
     * @return list<string>
     */
    public function audiencesFor(UuidV7 $moduleId): array;

    /**
     * @return list<string>
     */
    public function allowedScopesFor(UuidV7 $moduleId): array;

    /**
     * @param  list<UuidV7>  $ids
     * @return list<ModuleDefinition>
     */
    public function findOperationalByIds(array $ids): array;
}
