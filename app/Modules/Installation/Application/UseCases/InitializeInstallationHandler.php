<?php

declare(strict_types=1);

namespace App\Modules\Installation\Application\UseCases;

use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Modules\Identity\Application\DTO\CreateIdentityData;
use App\Modules\Identity\Application\Ports\In\CreateIdentity;
use App\Modules\Installation\Application\DTO\InitializeInstallationData;
use App\Modules\Installation\Application\DTO\InstallationDetails;
use App\Modules\Installation\Application\InstallationDefaults;
use App\Modules\Installation\Application\Ports\In\InitializeInstallation;
use App\Modules\Installation\Application\Ports\Out\InstallationStore;
use App\Modules\Installation\Application\Services\InstallationChangeRecorder;
use App\Modules\Installation\Domain\Entities\Installation;
use App\Modules\Installation\Domain\ValueObjects\InstallationSettings;
use App\Modules\Installation\Domain\ValueObjects\InstallationState;
use App\Shared\Application\Contracts\Clock;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Application\Contracts\UuidGenerator;
use App\Shared\Domain\ValueObjects\UuidV7;

final readonly class InitializeInstallationHandler implements InitializeInstallation
{
    public function __construct(
        private InstallationStore $installations,
        private CreateIdentity $identities,
        private InstallationDefaults $defaults,
        private InstallationChangeRecorder $changes,
        private UuidGenerator $uuidGenerator,
        private Clock $clock,
        private TransactionManager $transactions,
    ) {}

    public function initialize(InitializeInstallationData $data): InstallationDetails
    {
        return $this->transactions->run(function () use ($data): InstallationDetails {
            $this->installations->acquireInitializationLock();
            $existing = $this->installations->find(forUpdate: true);

            if ($existing !== null) {
                return InstallationDetails::fromInstallation($existing);
            }

            $actor = AuditActor::system();
            $owner = $this->identities->create(new CreateIdentityData(
                email: $data->ownerEmail,
                displayName: $data->ownerDisplayName,
                temporaryPassword: $data->temporaryPassword,
                actor: $actor,
                correlation: $data->correlation,
            ));
            $now = $this->clock->now();
            $installation = new Installation(
                id: $this->uuidGenerator->generate(),
                ownerIdentityId: UuidV7::fromString($owner->id),
                state: InstallationState::PendingMfa,
                settings: new InstallationSettings(
                    displayName: $this->defaults->displayName,
                    shortName: null,
                    legalName: null,
                    institutionalDescription: null,
                    logoUrl: null,
                    logoDarkUrl: null,
                    faviconUrl: null,
                    primaryColor: null,
                    secondaryColor: null,
                    accentColor: null,
                    locale: $this->defaults->locale,
                    timezone: $this->defaults->timezone,
                    senderName: null,
                    senderEmail: null,
                    supportEmail: null,
                    supportUrl: null,
                    termsUrl: null,
                    privacyPolicyUrl: null,
                ),
                createdAt: $now,
                updatedAt: $now,
            );

            $this->installations->insert($installation);
            $this->changes->record(
                installation: $installation,
                actor: $actor,
                correlation: $data->correlation,
                action: 'installation.initialized',
                eventName: 'installation.initialized.v1',
                occurredAt: $now,
                after: [
                    'owner_identity_id' => $installation->ownerIdentityId()->toString(),
                    'state' => $installation->state()->value,
                    'settings' => $installation->settings()->toArray(),
                ],
            );

            return InstallationDetails::fromInstallation($installation, created: true);
        });
    }
}
