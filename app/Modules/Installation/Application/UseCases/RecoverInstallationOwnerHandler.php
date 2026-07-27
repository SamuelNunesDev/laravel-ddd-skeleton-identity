<?php

declare(strict_types=1);

namespace App\Modules\Installation\Application\UseCases;

use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Modules\Identity\Application\DTO\IdentityDetails;
use App\Modules\Identity\Application\DTO\ResetTemporaryPasswordData;
use App\Modules\Identity\Application\Ports\In\ManageIdentityCredentials;
use App\Modules\Installation\Application\DTO\RecoverOwnerData;
use App\Modules\Installation\Application\Ports\In\RecoverInstallationOwner;
use App\Modules\Installation\Application\Ports\Out\InstallationStore;
use App\Modules\Installation\Application\Services\InstallationChangeRecorder;
use App\Modules\Installation\Domain\Exceptions\InstallationNotFound;
use App\Shared\Application\Contracts\Clock;
use App\Shared\Application\Contracts\TransactionManager;

final readonly class RecoverInstallationOwnerHandler implements RecoverInstallationOwner
{
    public function __construct(
        private InstallationStore $installations,
        private ManageIdentityCredentials $credentials,
        private InstallationChangeRecorder $changes,
        private Clock $clock,
        private TransactionManager $transactions,
    ) {}

    public function recover(RecoverOwnerData $data): IdentityDetails
    {
        return $this->transactions->run(function () use ($data): IdentityDetails {
            $installation = $this->installations->find(forUpdate: true);

            if ($installation === null) {
                throw new InstallationNotFound;
            }

            $actor = AuditActor::system();
            $owner = $this->credentials->resetTemporaryPassword(new ResetTemporaryPasswordData(
                identityId: $installation->ownerIdentityId(),
                temporaryPassword: $data->temporaryPassword,
                actor: $actor,
                correlation: $data->correlation,
                reason: $data->reason,
            ));
            $now = $this->clock->now();
            $this->changes->record(
                installation: $installation,
                actor: $actor,
                correlation: $data->correlation,
                action: 'installation.owner_recovered',
                eventName: 'installation.owner.recovered.v1',
                occurredAt: $now,
                metadata: ['reason' => $data->reason],
            );

            return $owner;
        });
    }
}
