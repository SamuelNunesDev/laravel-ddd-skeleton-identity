<?php

declare(strict_types=1);

namespace App\Modules\Organization\Infrastructure\Persistence\Adapters;

use App\Modules\Organization\Application\Ports\Out\OrganizationStore;
use App\Modules\Organization\Domain\Entities\Organization;
use App\Modules\Organization\Domain\Exceptions\OrganizationIdentifierAlreadyExists;
use App\Modules\Organization\Domain\ValueObjects\MfaPolicy;
use App\Modules\Organization\Domain\ValueObjects\OrganizationIdentifier;
use App\Modules\Organization\Domain\ValueObjects\OrganizationStatus;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use stdClass;

final readonly class PostgresOrganizationStore implements OrganizationStore
{
    public function __construct(private DatabaseManager $database) {}

    public function findById(UuidV7 $id, bool $forUpdate = false): ?Organization
    {
        $query = $this->table()->where('id', $id->toString());

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $this->map($query->first());
    }

    public function identifierExists(OrganizationIdentifier $identifier): bool
    {
        return $this->table()->where('identifier', $identifier->value)->exists();
    }

    public function insert(Organization $organization): void
    {
        try {
            $this->table()->insert($this->values($organization));
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'organizations_identifier_unique')) {
                throw new OrganizationIdentifierAlreadyExists($exception);
            }

            throw $exception;
        }
    }

    public function update(Organization $organization): void
    {
        $this->table()
            ->where('id', $organization->id()->toString())
            ->update($this->values($organization));
    }

    public function findOperationalByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return array_values($this->table()
            ->whereIn('id', array_map(static fn (UuidV7 $id): string => $id->toString(), $ids))
            ->where('status', OrganizationStatus::Active->value)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (stdClass $row): Organization => $this->mapRow($row))
            ->all());
    }

    private function table(): Builder
    {
        return $this->database->connection()->table('organizations');
    }

    /**
     * @return array<string, string|null>
     */
    private function values(Organization $organization): array
    {
        return [
            'id' => $organization->id()->toString(),
            'identifier' => $organization->identifier()->value,
            'name' => $organization->name(),
            'mfa_policy' => $organization->mfaPolicy()->value,
            'status' => $organization->status()->value,
            'disabled_at' => $this->format($organization->disabledAt()),
            'deleted_at' => $this->format($organization->deletedAt()),
            'restored_at' => $this->format($organization->restoredAt()),
            'created_at' => $this->format($organization->createdAt()),
            'updated_at' => $this->format($organization->updatedAt()),
        ];
    }

    private function map(?stdClass $row): ?Organization
    {
        return $row === null ? null : $this->mapRow($row);
    }

    private function mapRow(stdClass $row): Organization
    {
        return Organization::reconstitute(
            id: UuidV7::fromString((string) $row->id),
            identifier: new OrganizationIdentifier((string) $row->identifier),
            name: (string) $row->name,
            mfaPolicy: MfaPolicy::from((string) $row->mfa_policy),
            status: OrganizationStatus::from((string) $row->status),
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
