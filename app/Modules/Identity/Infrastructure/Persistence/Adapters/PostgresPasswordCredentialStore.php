<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Adapters;

use App\Modules\Identity\Application\Ports\Out\PasswordCredentialStore;
use App\Modules\Identity\Domain\Entities\PasswordCredential;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use stdClass;

final readonly class PostgresPasswordCredentialStore implements PasswordCredentialStore
{
    public function __construct(private DatabaseManager $database) {}

    public function findByIdentityId(UuidV7 $identityId, bool $forUpdate = false): ?PasswordCredential
    {
        $query = $this->table()->where('identity_id', $identityId->toString());

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $this->map($query->first());
    }

    public function save(PasswordCredential $credential): void
    {
        $this->table()->upsert(
            [[
                'identity_id' => $credential->identityId->toString(),
                'password_hash' => $credential->passwordHash,
                'temporary_expires_at' => $this->format($credential->temporaryExpiresAt),
                'changed_at' => $this->format($credential->changedAt),
                'created_at' => $this->format($credential->createdAt),
                'updated_at' => $this->format($credential->updatedAt),
            ]],
            ['identity_id'],
            ['password_hash', 'temporary_expires_at', 'changed_at', 'updated_at'],
        );
    }

    private function table(): Builder
    {
        return $this->database->connection()->table('identity_credentials');
    }

    private function map(?stdClass $row): ?PasswordCredential
    {
        if ($row === null) {
            return null;
        }

        return new PasswordCredential(
            identityId: UuidV7::fromString((string) $row->identity_id),
            passwordHash: (string) $row->password_hash,
            temporaryExpiresAt: $this->date($row->temporary_expires_at),
            changedAt: $this->date($row->changed_at),
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
