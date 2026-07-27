<?php

declare(strict_types=1);

namespace App\Modules\Organization\Infrastructure\Persistence\Adapters;

use App\Modules\Organization\Application\Ports\Out\MembershipStore;
use App\Modules\Organization\Domain\Entities\Membership;
use App\Modules\Organization\Domain\Exceptions\MembershipAlreadyActive;
use App\Modules\Organization\Domain\ValueObjects\MembershipStatus;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use stdClass;

final readonly class PostgresMembershipStore implements MembershipStore
{
    public function __construct(private DatabaseManager $database) {}

    public function findById(UuidV7 $id, bool $forUpdate = false): ?Membership
    {
        $query = $this->table()->where('id', $id->toString());

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $this->map($query->first());
    }

    public function findActive(UuidV7 $identityId, UuidV7 $organizationId, bool $forUpdate = false): ?Membership
    {
        $query = $this->table()
            ->where('identity_id', $identityId->toString())
            ->where('organization_id', $organizationId->toString())
            ->where('status', MembershipStatus::Active->value)
            ->whereNull('ended_at');

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $this->map($query->first());
    }

    public function insert(Membership $membership): void
    {
        try {
            $this->table()->insert($this->values($membership));
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'memberships_identity_organization_active_unique')) {
                throw new MembershipAlreadyActive($exception);
            }

            throw $exception;
        }
    }

    public function update(Membership $membership): void
    {
        $this->table()
            ->where('id', $membership->id()->toString())
            ->update($this->values($membership));
    }

    public function activeOrganizationIdsFor(UuidV7 $identityId): array
    {
        return array_values($this->table()
            ->where('identity_id', $identityId->toString())
            ->where('status', MembershipStatus::Active->value)
            ->whereNull('ended_at')
            ->orderBy('organization_id')
            ->pluck('organization_id')
            ->map(static fn (mixed $id): UuidV7 => UuidV7::fromString((string) $id))
            ->all());
    }

    public function forOrganization(UuidV7 $organizationId): array
    {
        return array_values($this->table()
            ->where('organization_id', $organizationId->toString())
            ->orderBy('started_at')
            ->orderBy('id')
            ->get()
            ->map(fn (stdClass $row): Membership => $this->mapRow($row))
            ->all());
    }

    private function table(): Builder
    {
        return $this->database->connection()->table('memberships');
    }

    /**
     * @return array<string, string|null>
     */
    private function values(Membership $membership): array
    {
        return [
            'id' => $membership->id()->toString(),
            'identity_id' => $membership->identityId()->toString(),
            'organization_id' => $membership->organizationId()->toString(),
            'status' => $membership->status()->value,
            'started_at' => $membership->startedAt()->format('Y-m-d H:i:s.uP'),
            'ended_at' => $membership->endedAt()?->format('Y-m-d H:i:s.uP'),
            'created_at' => $membership->createdAt()->format('Y-m-d H:i:s.uP'),
            'updated_at' => $membership->updatedAt()->format('Y-m-d H:i:s.uP'),
        ];
    }

    private function map(?stdClass $row): ?Membership
    {
        return $row === null ? null : $this->mapRow($row);
    }

    private function mapRow(stdClass $row): Membership
    {
        return new Membership(
            id: UuidV7::fromString((string) $row->id),
            identityId: UuidV7::fromString((string) $row->identity_id),
            organizationId: UuidV7::fromString((string) $row->organization_id),
            status: MembershipStatus::from((string) $row->status),
            startedAt: new DateTimeImmutable((string) $row->started_at),
            endedAt: $row->ended_at === null ? null : new DateTimeImmutable((string) $row->ended_at),
            createdAt: new DateTimeImmutable((string) $row->created_at),
            updatedAt: new DateTimeImmutable((string) $row->updated_at),
        );
    }
}
