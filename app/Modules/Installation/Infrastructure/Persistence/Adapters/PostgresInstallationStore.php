<?php

declare(strict_types=1);

namespace App\Modules\Installation\Infrastructure\Persistence\Adapters;

use App\Modules\Installation\Application\Ports\Out\InstallationStore;
use App\Modules\Installation\Domain\Entities\Installation;
use App\Modules\Installation\Domain\ValueObjects\InstallationSettings;
use App\Modules\Installation\Domain\ValueObjects\InstallationState;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use stdClass;

final readonly class PostgresInstallationStore implements InstallationStore
{
    private const INITIALIZATION_LOCK = 691_871_141;

    public function __construct(private DatabaseManager $database) {}

    public function acquireInitializationLock(): void
    {
        $connection = $this->database->connection();

        if ($connection->getDriverName() === 'pgsql') {
            $connection->select('SELECT pg_advisory_xact_lock(?)', [self::INITIALIZATION_LOCK]);
        }
    }

    public function find(bool $forUpdate = false): ?Installation
    {
        $query = $this->table()->where('singleton_key', 1);

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $this->map($query->first());
    }

    public function insert(Installation $installation): void
    {
        $this->table()->insert($this->values($installation));
    }

    public function update(Installation $installation): void
    {
        $this->table()
            ->where('singleton_key', 1)
            ->update($this->values($installation));
    }

    private function table(): Builder
    {
        return $this->database->connection()->table('installations');
    }

    /**
     * @return array<string, int|string|null>
     */
    private function values(Installation $installation): array
    {
        return [
            'id' => $installation->id()->toString(),
            'singleton_key' => 1,
            'owner_identity_id' => $installation->ownerIdentityId()->toString(),
            'state' => $installation->state()->value,
            ...$installation->settings()->toArray(),
            'created_at' => $installation->createdAt()->format('Y-m-d H:i:s.uP'),
            'updated_at' => $installation->updatedAt()->format('Y-m-d H:i:s.uP'),
        ];
    }

    private function map(?stdClass $row): ?Installation
    {
        if ($row === null) {
            return null;
        }

        return new Installation(
            id: UuidV7::fromString((string) $row->id),
            ownerIdentityId: UuidV7::fromString((string) $row->owner_identity_id),
            state: InstallationState::from((string) $row->state),
            settings: new InstallationSettings(
                displayName: (string) $row->display_name,
                shortName: $this->nullableString($row->short_name),
                legalName: $this->nullableString($row->legal_name),
                institutionalDescription: $this->nullableString($row->institutional_description),
                logoUrl: $this->nullableString($row->logo_url),
                logoDarkUrl: $this->nullableString($row->logo_dark_url),
                faviconUrl: $this->nullableString($row->favicon_url),
                primaryColor: $this->nullableString($row->primary_color),
                secondaryColor: $this->nullableString($row->secondary_color),
                accentColor: $this->nullableString($row->accent_color),
                locale: (string) $row->locale,
                timezone: (string) $row->timezone,
                senderName: $this->nullableString($row->sender_name),
                senderEmail: $this->nullableString($row->sender_email),
                supportEmail: $this->nullableString($row->support_email),
                supportUrl: $this->nullableString($row->support_url),
                termsUrl: $this->nullableString($row->terms_url),
                privacyPolicyUrl: $this->nullableString($row->privacy_policy_url),
            ),
            createdAt: new DateTimeImmutable((string) $row->created_at),
            updatedAt: new DateTimeImmutable((string) $row->updated_at),
        );
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
