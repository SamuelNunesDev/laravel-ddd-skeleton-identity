<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Infrastructure\Persistence\Adapters;

use App\Modules\ModuleCatalog\Application\Ports\Out\ModuleStore;
use App\Modules\ModuleCatalog\Domain\Entities\ModuleDefinition;
use App\Modules\ModuleCatalog\Domain\Exceptions\ModuleIdentifierAlreadyExists;
use App\Modules\ModuleCatalog\Domain\ValueObjects\AllowedScope;
use App\Modules\ModuleCatalog\Domain\ValueObjects\Audience;
use App\Modules\ModuleCatalog\Domain\ValueObjects\ModuleIdentifier;
use App\Modules\ModuleCatalog\Domain\ValueObjects\ModuleStatus;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use stdClass;

final readonly class PostgresModuleStore implements ModuleStore
{
    public function __construct(private DatabaseManager $database) {}

    public function findById(UuidV7 $id, bool $forUpdate = false): ?ModuleDefinition
    {
        $query = $this->table()->where('id', $id->toString());

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $this->map($query->first());
    }

    public function identifierExists(ModuleIdentifier $identifier): bool
    {
        return $this->table()->where('identifier', $identifier->value)->exists();
    }

    public function insert(ModuleDefinition $module): void
    {
        try {
            $this->table()->insert($this->values($module));
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'modules_identifier_unique')) {
                throw new ModuleIdentifierAlreadyExists($exception);
            }

            throw $exception;
        }
    }

    public function update(ModuleDefinition $module): void
    {
        $this->table()
            ->where('id', $module->id()->toString())
            ->update($this->values($module));
    }

    public function replaceProtocolMetadata(
        UuidV7 $moduleId,
        array $audiences,
        array $allowedScopes,
        DateTimeImmutable $now,
    ): void {
        $timestamp = $now->format('Y-m-d H:i:s.uP');
        $connection = $this->database->connection();
        $connection->table('module_audiences')
            ->where('module_id', $moduleId->toString())
            ->update(['active' => false, 'retired_at' => $timestamp, 'updated_at' => $timestamp]);
        $connection->table('module_allowed_scopes')
            ->where('module_id', $moduleId->toString())
            ->update(['active' => false, 'retired_at' => $timestamp, 'updated_at' => $timestamp]);

        if ($audiences !== []) {
            $connection->table('module_audiences')->upsert(
                array_map(
                    static fn (Audience $audience): array => [
                        'module_id' => $moduleId->toString(),
                        'audience' => $audience->value,
                        'active' => true,
                        'retired_at' => null,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ],
                    $audiences,
                ),
                ['module_id', 'audience'],
                ['active', 'retired_at', 'updated_at'],
            );
        }

        if ($allowedScopes !== []) {
            $connection->table('module_allowed_scopes')->upsert(
                array_map(
                    static fn (AllowedScope $scope): array => [
                        'module_id' => $moduleId->toString(),
                        'scope' => $scope->value,
                        'active' => true,
                        'retired_at' => null,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ],
                    $allowedScopes,
                ),
                ['module_id', 'scope'],
                ['active', 'retired_at', 'updated_at'],
            );
        }
    }

    public function audiencesFor(UuidV7 $moduleId): array
    {
        return array_values($this->database->connection()
            ->table('module_audiences')
            ->where('module_id', $moduleId->toString())
            ->where('active', true)
            ->whereNull('retired_at')
            ->orderBy('audience')
            ->pluck('audience')
            ->map(static fn (mixed $value): string => (string) $value)
            ->all());
    }

    public function allowedScopesFor(UuidV7 $moduleId): array
    {
        return array_values($this->database->connection()
            ->table('module_allowed_scopes')
            ->where('module_id', $moduleId->toString())
            ->where('active', true)
            ->whereNull('retired_at')
            ->orderBy('scope')
            ->pluck('scope')
            ->map(static fn (mixed $value): string => (string) $value)
            ->all());
    }

    public function findOperationalByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return array_values($this->table()
            ->whereIn('id', array_map(static fn (UuidV7 $id): string => $id->toString(), $ids))
            ->where('status', ModuleStatus::Active->value)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (stdClass $row): ModuleDefinition => $this->mapRow($row))
            ->all());
    }

    private function table(): Builder
    {
        return $this->database->connection()->table('modules');
    }

    /**
     * @return array<string, string|null>
     */
    private function values(ModuleDefinition $module): array
    {
        return [
            'id' => $module->id()->toString(),
            'identifier' => $module->identifier()->value,
            'name' => $module->name(),
            'description' => $module->description(),
            'status' => $module->status()->value,
            'disabled_at' => $this->format($module->disabledAt()),
            'deleted_at' => $this->format($module->deletedAt()),
            'restored_at' => $this->format($module->restoredAt()),
            'created_at' => $this->format($module->createdAt()),
            'updated_at' => $this->format($module->updatedAt()),
        ];
    }

    private function map(?stdClass $row): ?ModuleDefinition
    {
        return $row === null ? null : $this->mapRow($row);
    }

    private function mapRow(stdClass $row): ModuleDefinition
    {
        return ModuleDefinition::reconstitute(
            id: UuidV7::fromString((string) $row->id),
            identifier: new ModuleIdentifier((string) $row->identifier),
            name: (string) $row->name,
            description: (string) $row->description,
            status: ModuleStatus::from((string) $row->status),
            disabledAt: $this->date($row->disabled_at),
            deletedAt: $this->date($row->deleted_at),
            restoredAt: $this->date($row->restored_at),
            createdAt: new DateTimeImmutable((string) $row->created_at),
            updatedAt: new DateTimeImmutable((string) $row->updated_at),
        );
    }

    private function format(?DateTimeImmutable $date): ?string
    {
        return $date?->format('Y-m-d H:i:s.uP');
    }

    private function date(mixed $value): ?DateTimeImmutable
    {
        return $value === null ? null : new DateTimeImmutable((string) $value);
    }
}
