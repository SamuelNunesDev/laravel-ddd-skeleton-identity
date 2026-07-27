<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Identity\Application\DTO\CreateIdentityData;
use App\Modules\Identity\Application\DTO\IdentityDetails;
use App\Modules\Identity\Application\IdentitySecurityConfiguration;
use App\Modules\Identity\Application\Ports\In\CreateIdentity;
use App\Modules\Identity\Application\Ports\Out\IdentityStore;
use App\Modules\Identity\Application\Ports\Out\PasswordCredentialStore;
use App\Modules\Identity\Application\Ports\Out\PasswordHasher;
use App\Modules\Identity\Application\Services\IdentityChangeRecorder;
use App\Modules\Identity\Domain\Entities\Identity;
use App\Modules\Identity\Domain\Entities\PasswordCredential;
use App\Modules\Identity\Domain\Exceptions\IdentityEmailAlreadyExists;
use App\Modules\Identity\Domain\ValueObjects\EmailAddress;
use App\Shared\Application\Contracts\Clock;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Application\Contracts\UuidGenerator;

final readonly class CreateIdentityHandler implements CreateIdentity
{
    public function __construct(
        private IdentityStore $identities,
        private PasswordCredentialStore $credentials,
        private PasswordHasher $passwordHasher,
        private IdentitySecurityConfiguration $security,
        private IdentityChangeRecorder $changes,
        private UuidGenerator $uuidGenerator,
        private Clock $clock,
        private TransactionManager $transactions,
    ) {}

    public function create(CreateIdentityData $data): IdentityDetails
    {
        $email = new EmailAddress($data->email);
        $this->security->assertPassword($data->temporaryPassword);

        return $this->transactions->run(function () use ($data, $email): IdentityDetails {
            if ($this->identities->findActiveByEmail($email) !== null) {
                throw new IdentityEmailAlreadyExists;
            }

            $now = $this->clock->now();
            $identity = Identity::create(
                id: $this->uuidGenerator->generate(),
                email: $email,
                displayName: $data->displayName,
                now: $now,
            );
            $expiresAt = $this->security->temporaryExpiration($now, $data->temporaryPasswordHours);

            $this->identities->insert($identity);
            $this->credentials->save(new PasswordCredential(
                identityId: $identity->id(),
                passwordHash: $this->passwordHasher->hash($data->temporaryPassword),
                temporaryExpiresAt: $expiresAt,
                changedAt: null,
                createdAt: $now,
                updatedAt: $now,
            ));
            $this->changes->record(
                identity: $identity,
                actor: $data->actor,
                correlation: $data->correlation,
                action: 'identity.created',
                eventName: 'identity.created.v1',
                occurredAt: $now,
                after: [
                    'email' => $identity->email()->value,
                    'display_name' => $identity->displayName(),
                    'status' => $identity->status()->value,
                    'must_change_password' => true,
                ],
                metadata: ['temporary_expires_at' => $expiresAt->format(DATE_ATOM)],
            );

            return IdentityDetails::fromIdentity($identity);
        });
    }
}
