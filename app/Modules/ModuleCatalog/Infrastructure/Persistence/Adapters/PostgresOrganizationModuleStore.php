<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Infrastructure\Persistence\Adapters;

use App\Modules\ModuleCatalog\Application\Ports\Out\OrganizationModuleStore;
use App\Modules\ModuleCatalog\Domain\Entities\OrganizationModule;
use App\Modules\ModuleCatalog\Domain\Exceptions\OrganizationModuleAlreadyEnabled;
use App\Modules\ModuleCatalog\Domain\ValueObjects\OrganizationModuleStatus;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use stdClass;

final readonly class PostgresOrganizationModuleStore implements OrganizationModuleStore
{
    public function __construct(private DatabaseManager $database) {}

    public function findEnabled(
        UuidV7 $organizationId,
        UuidV7 $moduleId,
        bool $forUpdate = false,
    ): ?OrganizationModule {
        $query = $this->table()
            ->where('organization_id', $organizationId->toString())
            ->where('module_id', $moduleId->toString())
            ->where('status', OrganizationModuleStatus::Enabled->value)
            ->whereNull('disabled_at');

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $this->map($query->first());
    }

    public function insert(OrganizationModule $organizationModule): void
    {
        try {
            $this->table()->insert($this->values($organizationModule));
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'organization_modules_active_unique')) {
                throw new OrganizationModuleAlreadyEnabled($exception);
            }

            throw $exception;
        }
    }

    public function update(OrganizationModule $organizationModule): void
    {
        $this->table()
            ->where('id', $organizationModule->id()->toString())
            ->update($this->values($organizationModule));
    }

    public function enabledModuleIdsFor(UuidV7 $organizationId): array
    {
        return array_values($this->table()
            ->where('organization_id', $organizationId->toString())
            ->where('status', OrganizationModuleStatus::Enabled->value)
            ->whereNull('disabled_at')
            ->orderBy('module_id')
            ->pluck('module_id')
            ->map(static fn (mixed $id): UuidV7 => UuidV7::fromString((string) $id))
            ->all());
    }

    public function filterEnabledOrganizationIds(UuidV7 $moduleId, array $organizationIds): array
    {
        if ($organizationIds === []) {
            return [];
        }

        return array_values($this->table()
            ->where('module_id', $moduleId->toString())
            ->whereIn(
                'organization_id',
                array_map(static fn (UuidV7 $id): string => $id->toString(), $organizationIds),
            )
            ->where('status', OrganizationModuleStatus::Enabled->value)
            ->whereNull('disabled_at')
            ->orderBy('organization_id')
            ->pluck('organization_id')
            ->map(static fn (mixed $id): UuidV7 => UuidV7::fromString((string) $id))
            ->all());
    }

    public function enabledOrganizationIdsFor(UuidV7 $moduleId): array
    {
        return array_values($this->table()
            ->where('module_id', $moduleId->toString())
            ->where('status', OrganizationModuleStatus::Enabled->value)
            ->whereNull('disabled_at')
            ->orderBy('organization_id')
            ->pluck('organization_id')
            ->map(static fn (mixed $id): UuidV7 => UuidV7::fromString((string) $id))
            ->all());
    }

    private function table(): Builder
    {
        return $this->database->connection()->table('organization_modules');
    }

    /**
     * @return array<string, string|null>
     */
    private function values(OrganizationModule $organizationModule): array
    {
        return [
            'id' => $organizationModule->id()->toString(),
            'organization_id' => $organizationModule->organizationId()->toString(),
            'module_id' => $organizationModule->moduleId()->toString(),
            'status' => $organizationModule->status()->value,
            'enabled_at' => $organizationModule->enabledAt()->format('Y-m-d H:i:s.uP'),
            'disabled_at' => $organizationModule->disabledAt()?->format('Y-m-d H:i:s.uP'),
            'created_at' => $organizationModule->createdAt()->format('Y-m-d H:i:s.uP'),
            'updated_at' => $organizationModule->updatedAt()->format('Y-m-d H:i:s.uP'),
        ];
    }

    private function map(?stdClass $row): ?OrganizationModule
    {
        if ($row === null) {
            return null;
        }

        return new OrganizationModule(
            id: UuidV7::fromString((string) $row->id),
            organizationId: UuidV7::fromString((string) $row->organization_id),
            moduleId: UuidV7::fromString((string) $row->module_id),
            status: OrganizationModuleStatus::from((string) $row->status),
            enabledAt: new DateTimeImmutable((string) $row->enabled_at),
            disabledAt: $row->disabled_at === null ? null : new DateTimeImmutable((string) $row->disabled_at),
            createdAt: new DateTimeImmutable((string) $row->created_at),
            updatedAt: new DateTimeImmutable((string) $row->updated_at),
        );
    }
}
