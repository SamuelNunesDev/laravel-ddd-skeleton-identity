<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Identity\Application\DTO\IdentityDetails;
use App\Modules\Identity\Application\DTO\IdentityLifecycleData;
use App\Modules\Identity\Application\Ports\In\ManageIdentityLifecycle;
use App\Modules\Identity\Application\Ports\Out\IdentityLifecycleProtection;
use App\Modules\Identity\Application\Ports\Out\IdentityStore;
use App\Modules\Identity\Application\Services\IdentityChangeRecorder;
use App\Modules\Identity\Domain\Entities\Identity;
use App\Modules\Identity\Domain\Exceptions\IdentityEmailAlreadyExists;
use App\Modules\Identity\Domain\Exceptions\IdentityNotFound;
use App\Modules\Identity\Domain\Exceptions\ProtectedInstallationOwner;
use App\Shared\Application\Contracts\Clock;
use App\Shared\Application\Contracts\TransactionManager;

final readonly class IdentityLifecycleHandler implements ManageIdentityLifecycle
{
    public function __construct(
        private IdentityStore $identities,
        private IdentityLifecycleProtection $protection,
        private IdentityChangeRecorder $changes,
        private Clock $clock,
        private TransactionManager $transactions,
    ) {}

    public function deactivate(IdentityLifecycleData $data): IdentityDetails
    {
        return $this->change($data, 'deactivate');
    }

    public function reactivate(IdentityLifecycleData $data): IdentityDetails
    {
        return $this->change($data, 'reactivate');
    }

    public function softDelete(IdentityLifecycleData $data): IdentityDetails
    {
        return $this->change($data, 'soft_delete');
    }

    public function restore(IdentityLifecycleData $data): IdentityDetails
    {
        return $this->change($data, 'restore');
    }

    private function change(IdentityLifecycleData $data, string $operation): IdentityDetails
    {
        return $this->transactions->run(function () use ($data, $operation): IdentityDetails {
            $identity = $this->identities->findById($data->identityId, forUpdate: true);

            if ($identity === null) {
                throw new IdentityNotFound;
            }

            if (in_array($operation, ['deactivate', 'soft_delete'], true)
                && $this->protection->isProtectedOwner($identity->id())) {
                throw new ProtectedInstallationOwner;
            }

            if ($operation === 'restore'
                && $this->identities->emailExistsForOtherActiveIdentity($identity->email(), $identity->id())) {
                throw new IdentityEmailAlreadyExists;
            }

            $before = $this->lifecycleSnapshot($identity);
            $now = $this->clock->now();

            if ($operation === 'deactivate') {
                $identity->deactivate($now);
            } elseif ($operation === 'reactivate') {
                $identity->reactivate($now);
            } elseif ($operation === 'soft_delete') {
                $identity->softDelete($now);
            } else {
                $identity->restore($now);
            }

            $this->identities->update($identity);
            $this->changes->record(
                identity: $identity,
                actor: $data->actor,
                correlation: $data->correlation,
                action: 'identity.'.$operation,
                eventName: 'identity.lifecycle.changed.v1',
                occurredAt: $now,
                before: $before,
                after: $this->lifecycleSnapshot($identity),
                metadata: ['reason' => $data->reason],
            );

            return IdentityDetails::fromIdentity($identity);
        });
    }

    /**
     * @return array<string, int|string|bool|null>
     */
    private function lifecycleSnapshot(Identity $identity): array
    {
        return [
            'status' => $identity->status()->value,
            'deleted_at' => $identity->deletedAt()?->format(DATE_ATOM),
            'must_change_password' => $identity->mustChangePassword(),
            'authorization_version' => $identity->authorizationVersion(),
        ];
    }
}
