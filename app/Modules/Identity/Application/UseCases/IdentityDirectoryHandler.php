<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Identity\Application\DTO\IdentityDetails;
use App\Modules\Identity\Application\DTO\UpdateIdentityData;
use App\Modules\Identity\Application\Ports\In\IdentityDirectory;
use App\Modules\Identity\Application\Ports\Out\IdentityStore;
use App\Modules\Identity\Application\Services\IdentityChangeRecorder;
use App\Modules\Identity\Domain\Exceptions\IdentityEmailAlreadyExists;
use App\Modules\Identity\Domain\Exceptions\IdentityNotFound;
use App\Modules\Identity\Domain\ValueObjects\EmailAddress;
use App\Shared\Application\Contracts\Clock;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\ValueObjects\UuidV7;

final readonly class IdentityDirectoryHandler implements IdentityDirectory
{
    public function __construct(
        private IdentityStore $identities,
        private IdentityChangeRecorder $changes,
        private Clock $clock,
        private TransactionManager $transactions,
    ) {}

    public function get(UuidV7 $identityId): IdentityDetails
    {
        $identity = $this->identities->findById($identityId);

        if ($identity === null) {
            throw new IdentityNotFound;
        }

        return IdentityDetails::fromIdentity($identity);
    }

    public function update(UpdateIdentityData $data): IdentityDetails
    {
        return $this->transactions->run(function () use ($data): IdentityDetails {
            $identity = $this->identities->findById($data->identityId, forUpdate: true);

            if ($identity === null) {
                throw new IdentityNotFound;
            }

            $email = new EmailAddress($data->email);

            if ($this->identities->emailExistsForOtherActiveIdentity($email, $identity->id())) {
                throw new IdentityEmailAlreadyExists;
            }

            $before = [
                'email' => $identity->email()->value,
                'display_name' => $identity->displayName(),
            ];
            $now = $this->clock->now();
            $identity->updateProfile($email, $data->displayName, $now);
            $this->identities->update($identity);
            $this->changes->record(
                identity: $identity,
                actor: $data->actor,
                correlation: $data->correlation,
                action: 'identity.profile_updated',
                eventName: 'identity.profile.updated.v1',
                occurredAt: $now,
                before: $before,
                after: [
                    'email' => $identity->email()->value,
                    'display_name' => $identity->displayName(),
                ],
            );

            return IdentityDetails::fromIdentity($identity);
        });
    }
}
