<?php

declare(strict_types=1);

namespace App\Modules\Installation\Application\UseCases;

use App\Modules\Audit\Domain\ValueObjects\AuditActorType;
use App\Modules\Installation\Application\DTO\InstallationDetails;
use App\Modules\Installation\Application\DTO\UpdateInstallationSettingsData;
use App\Modules\Installation\Application\Ports\In\InstallationSettings;
use App\Modules\Installation\Application\Ports\Out\InstallationStore;
use App\Modules\Installation\Application\Services\InstallationChangeRecorder;
use App\Modules\Installation\Domain\Exceptions\InstallationNotFound;
use App\Modules\Installation\Domain\Exceptions\InstallationOwnerRequired;
use App\Shared\Application\Contracts\Clock;
use App\Shared\Application\Contracts\TransactionManager;

final readonly class InstallationSettingsHandler implements InstallationSettings
{
    public function __construct(
        private InstallationStore $installations,
        private InstallationChangeRecorder $changes,
        private Clock $clock,
        private TransactionManager $transactions,
    ) {}

    public function get(): InstallationDetails
    {
        $installation = $this->installations->find();

        if ($installation === null) {
            throw new InstallationNotFound;
        }

        return InstallationDetails::fromInstallation($installation);
    }

    public function update(UpdateInstallationSettingsData $data): InstallationDetails
    {
        return $this->transactions->run(function () use ($data): InstallationDetails {
            $installation = $this->installations->find(forUpdate: true);

            if ($installation === null) {
                throw new InstallationNotFound;
            }

            if ($data->actor->type !== AuditActorType::Identity
                || $data->actor->id === null
                || ! $data->actor->id->equals($installation->ownerIdentityId())) {
                throw new InstallationOwnerRequired;
            }

            $before = $installation->settings()->toArray();
            $now = $this->clock->now();
            $installation->updateSettings($data->settings, $now);
            $this->installations->update($installation);
            $this->changes->record(
                installation: $installation,
                actor: $data->actor,
                correlation: $data->correlation,
                action: 'installation.settings_updated',
                eventName: 'installation.settings.updated.v1',
                occurredAt: $now,
                before: $before,
                after: $installation->settings()->toArray(),
            );

            return InstallationDetails::fromInstallation($installation);
        });
    }
}
