<?php

declare(strict_types=1);

namespace App\Modules\Organization\Infrastructure\Persistence\Adapters;

use App\Modules\Organization\Application\Ports\Out\OrganizationPreferenceStore;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use Illuminate\Database\DatabaseManager;

final readonly class PostgresOrganizationPreferenceStore implements OrganizationPreferenceStore
{
    public function __construct(private DatabaseManager $database) {}

    public function findLastOrganizationId(UuidV7 $identityId): ?UuidV7
    {
        $value = $this->database->connection()
            ->table('identity_preferences')
            ->where('identity_id', $identityId->toString())
            ->value('last_organization_id');

        return $value === null ? null : UuidV7::fromString((string) $value);
    }

    public function save(UuidV7 $identityId, UuidV7 $organizationId, DateTimeImmutable $updatedAt): void
    {
        $this->database->connection()
            ->table('identity_preferences')
            ->upsert(
                [[
                    'identity_id' => $identityId->toString(),
                    'last_organization_id' => $organizationId->toString(),
                    'updated_at' => $updatedAt->format('Y-m-d H:i:s.uP'),
                ]],
                ['identity_id'],
                ['last_organization_id', 'updated_at'],
            );
    }
}
