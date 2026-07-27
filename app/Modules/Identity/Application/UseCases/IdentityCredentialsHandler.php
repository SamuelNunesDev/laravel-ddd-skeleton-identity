<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Modules\Identity\Application\DTO\ChangeTemporaryPasswordData;
use App\Modules\Identity\Application\DTO\CredentialVerification;
use App\Modules\Identity\Application\DTO\IdentityDetails;
use App\Modules\Identity\Application\DTO\ResetTemporaryPasswordData;
use App\Modules\Identity\Application\IdentitySecurityConfiguration;
use App\Modules\Identity\Application\Ports\In\ManageIdentityCredentials;
use App\Modules\Identity\Application\Ports\Out\IdentityStore;
use App\Modules\Identity\Application\Ports\Out\PasswordCredentialStore;
use App\Modules\Identity\Application\Ports\Out\PasswordHasher;
use App\Modules\Identity\Application\Services\IdentityChangeRecorder;
use App\Modules\Identity\Domain\Entities\PasswordCredential;
use App\Modules\Identity\Domain\Exceptions\IdentityNotFound;
use App\Modules\Identity\Domain\Exceptions\TemporaryCredentialInvalid;
use App\Modules\Identity\Domain\ValueObjects\EmailAddress;
use App\Shared\Application\Contracts\Clock;
use App\Shared\Application\Contracts\TransactionManager;
use InvalidArgumentException;

final readonly class IdentityCredentialsHandler implements ManageIdentityCredentials
{
    public function __construct(
        private IdentityStore $identities,
        private PasswordCredentialStore $credentials,
        private PasswordHasher $passwordHasher,
        private IdentitySecurityConfiguration $security,
        private IdentityChangeRecorder $changes,
        private Clock $clock,
        private TransactionManager $transactions,
    ) {}

    public function resetTemporaryPassword(ResetTemporaryPasswordData $data): IdentityDetails
    {
        $this->security->assertPassword($data->temporaryPassword);

        return $this->transactions->run(function () use ($data): IdentityDetails {
            $identity = $this->identities->findById($data->identityId, forUpdate: true);

            if ($identity === null || $identity->deletedAt() !== null) {
                throw new IdentityNotFound;
            }

            $existing = $this->credentials->findByIdentityId($identity->id(), forUpdate: true);

            if ($existing === null) {
                throw new IdentityNotFound;
            }

            $now = $this->clock->now();
            $expiresAt = $this->security->temporaryExpiration($now, $data->temporaryPasswordHours);
            $identity->credentialChanged(temporary: true, now: $now);
            $this->identities->update($identity);
            $this->credentials->save(new PasswordCredential(
                identityId: $identity->id(),
                passwordHash: $this->passwordHasher->hash($data->temporaryPassword),
                temporaryExpiresAt: $expiresAt,
                changedAt: null,
                createdAt: $existing->createdAt,
                updatedAt: $now,
            ));
            $this->changes->record(
                identity: $identity,
                actor: $data->actor,
                correlation: $data->correlation,
                action: 'identity.temporary_password_reset',
                eventName: 'identity.credential.changed.v1',
                occurredAt: $now,
                after: ['must_change_password' => true],
                metadata: [
                    'temporary_expires_at' => $expiresAt->format(DATE_ATOM),
                    'reason' => $data->reason,
                ],
            );

            return IdentityDetails::fromIdentity($identity);
        });
    }

    public function changeTemporaryPassword(ChangeTemporaryPasswordData $data): IdentityDetails
    {
        $this->security->assertPassword($data->newPassword);

        return $this->transactions->run(function () use ($data): IdentityDetails {
            $identity = $this->identities->findById($data->identityId, forUpdate: true);
            $credential = $this->credentials->findByIdentityId($data->identityId, forUpdate: true);
            $now = $this->clock->now();

            if ($identity === null
                || $credential === null
                || ! $identity->canAuthenticate()
                || ! $identity->mustChangePassword()
                || ! $credential->isTemporaryAndValidAt($now)
                || ! $this->passwordHasher->verify($data->temporaryPassword, $credential->passwordHash)) {
                throw new TemporaryCredentialInvalid;
            }

            $identity->credentialChanged(temporary: false, now: $now);
            $this->identities->update($identity);
            $this->credentials->save(new PasswordCredential(
                identityId: $identity->id(),
                passwordHash: $this->passwordHasher->hash($data->newPassword),
                temporaryExpiresAt: null,
                changedAt: $now,
                createdAt: $credential->createdAt,
                updatedAt: $now,
            ));
            $this->changes->record(
                identity: $identity,
                actor: AuditActor::identity($identity->id()),
                correlation: $data->correlation,
                action: 'identity.password_changed',
                eventName: 'identity.credential.changed.v1',
                occurredAt: $now,
                before: ['must_change_password' => true],
                after: ['must_change_password' => false],
            );

            return IdentityDetails::fromIdentity($identity);
        });
    }

    public function verify(string $email, string $password): CredentialVerification
    {
        try {
            $emailAddress = new EmailAddress($email);
        } catch (InvalidArgumentException) {
            $this->passwordHasher->consumeDummyVerification($password);

            return CredentialVerification::invalid();
        }

        $identity = $this->identities->findActiveByEmail($emailAddress);

        if ($identity === null) {
            $this->passwordHasher->consumeDummyVerification($password);

            return CredentialVerification::invalid();
        }

        $credential = $this->credentials->findByIdentityId($identity->id());

        if ($credential === null) {
            $this->passwordHasher->consumeDummyVerification($password);

            return CredentialVerification::invalid();
        }

        $passwordMatches = $this->passwordHasher->verify($password, $credential->passwordHash);
        $temporaryIsValid = ! $identity->mustChangePassword()
            || $credential->isTemporaryAndValidAt($this->clock->now());

        if (! $passwordMatches || ! $temporaryIsValid || ! $identity->canAuthenticate()) {
            return CredentialVerification::invalid();
        }

        return CredentialVerification::valid($identity->id(), $identity->mustChangePassword());
    }
}
