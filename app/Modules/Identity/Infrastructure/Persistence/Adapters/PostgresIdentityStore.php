<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Adapters;

use App\Modules\Identity\Application\Ports\Out\IdentityStore;
use App\Modules\Identity\Domain\Entities\Identity;
use App\Modules\Identity\Domain\Exceptions\IdentityEmailAlreadyExists;
use App\Modules\Identity\Domain\ValueObjects\EmailAddress;
use App\Modules\Identity\Domain\ValueObjects\IdentityStatus;
use App\Shared\Domain\ValueObjects\UuidV7;
use DateTimeImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use stdClass;

final readonly class PostgresIdentityStore implements IdentityStore
{
    public function __construct(private DatabaseManager $database) {}

    public function findById(UuidV7 $id, bool $forUpdate = false): ?Identity
    {
        $query = $this->table()->where('id', $id->toString());

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $this->map($query->first());
    }

    public function findActiveByEmail(EmailAddress $email): ?Identity
    {
        return $this->map(
            $this->table()
                ->where('email_normalized', $email->normalized)
                ->whereNull('deleted_at')
                ->first(),
        );
    }

    public function emailExistsForOtherActiveIdentity(EmailAddress $email, UuidV7 $identityId): bool
    {
        return $this->table()
            ->where('email_normalized', $email->normalized)
            ->where('id', '<>', $identityId->toString())
            ->whereNull('deleted_at')
            ->exists();
    }

    public function insert(Identity $identity): void
    {
        try {
            $this->table()->insert($this->values($identity));
        } catch (QueryException $exception) {
            $this->rethrowUniqueEmail($exception);
        }
    }

    public function update(Identity $identity): void
    {
        try {
            $this->table()
                ->where('id', $identity->id()->toString())
                ->update($this->values($identity));
        } catch (QueryException $exception) {
            $this->rethrowUniqueEmail($exception);
        }
    }

    private function table(): Builder
    {
        return $this->database->connection()->table('identities');
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    private function values(Identity $identity): array
    {
        return [
            'id' => $identity->id()->toString(),
            'email' => $identity->email()->value,
            'email_normalized' => $identity->email()->normalized,
            'display_name' => $identity->displayName(),
            'status' => $identity->status()->value,
            'must_change_password' => $identity->mustChangePassword(),
            'authorization_version' => $identity->authorizationVersion(),
            'disabled_at' => $this->format($identity->disabledAt()),
            'deleted_at' => $this->format($identity->deletedAt()),
            'restored_at' => $this->format($identity->restoredAt()),
            'created_at' => $this->format($identity->createdAt()),
            'updated_at' => $this->format($identity->updatedAt()),
        ];
    }

    private function map(?stdClass $row): ?Identity
    {
        if ($row === null) {
            return null;
        }

        return Identity::reconstitute(
            id: UuidV7::fromString((string) $row->id),
            email: new EmailAddress((string) $row->email),
            displayName: (string) $row->display_name,
            status: IdentityStatus::from((string) $row->status),
            mustChangePassword: (bool) $row->must_change_password,
            authorizationVersion: (int) $row->authorization_version,
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

    private function rethrowUniqueEmail(QueryException $exception): never
    {
        if (str_contains($exception->getMessage(), 'identities_email_normalized_active_unique')) {
            throw new IdentityEmailAlreadyExists(previous: $exception);
        }

        throw $exception;
    }
}
