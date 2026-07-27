<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Application\UseCases;

use App\Modules\ModuleCatalog\Application\DTO\ModuleDetails;
use App\Modules\ModuleCatalog\Application\DTO\ModuleLifecycleData;
use App\Modules\ModuleCatalog\Application\DTO\RegisterModuleData;
use App\Modules\ModuleCatalog\Application\DTO\UpdateModuleData;
use App\Modules\ModuleCatalog\Application\Ports\In\ManageModules;
use App\Modules\ModuleCatalog\Application\Ports\Out\ModuleStore;
use App\Modules\ModuleCatalog\Application\Services\ModuleCatalogChangeRecorder;
use App\Modules\ModuleCatalog\Domain\Entities\ModuleDefinition;
use App\Modules\ModuleCatalog\Domain\Exceptions\ModuleIdentifierAlreadyExists;
use App\Modules\ModuleCatalog\Domain\Exceptions\ModuleNotFound;
use App\Modules\ModuleCatalog\Domain\ValueObjects\AllowedScope;
use App\Modules\ModuleCatalog\Domain\ValueObjects\Audience;
use App\Modules\ModuleCatalog\Domain\ValueObjects\ModuleIdentifier;
use App\Shared\Application\Contracts\Clock;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Application\Contracts\UuidGenerator;
use App\Shared\Domain\ValueObjects\UuidV7;

final readonly class ManageModulesHandler implements ManageModules
{
    public function __construct(
        private ModuleStore $modules,
        private ModuleCatalogChangeRecorder $changes,
        private UuidGenerator $uuidGenerator,
        private Clock $clock,
        private TransactionManager $transactions,
    ) {}

    public function register(RegisterModuleData $data): ModuleDetails
    {
        $identifier = new ModuleIdentifier($data->identifier);
        $audiences = $this->audiences($data->audiences);
        $allowedScopes = $this->allowedScopes($data->allowedScopes);

        return $this->transactions->run(function () use ($data, $identifier, $audiences, $allowedScopes): ModuleDetails {
            if ($this->modules->identifierExists($identifier)) {
                throw new ModuleIdentifierAlreadyExists;
            }

            $now = $this->clock->now();
            $module = ModuleDefinition::create(
                id: $this->uuidGenerator->generate(),
                identifier: $identifier,
                name: $data->name,
                description: $data->description,
                now: $now,
            );
            $this->modules->insert($module);
            $this->modules->replaceProtocolMetadata($module->id(), $audiences, $allowedScopes, $now);
            $snapshot = $this->snapshot($module, $audiences, $allowedScopes);
            $this->changes->record(
                targetType: 'module',
                targetId: $module->id(),
                moduleId: $module->id(),
                organizationId: null,
                actor: $data->actor,
                correlation: $data->correlation,
                action: 'module.registered',
                eventName: 'module.registered.v1',
                occurredAt: $now,
                after: $snapshot,
                eventPayload: $snapshot,
            );

            return $this->details($module);
        });
    }

    public function get(UuidV7 $moduleId): ModuleDetails
    {
        $module = $this->modules->findById($moduleId);

        if ($module === null) {
            throw new ModuleNotFound;
        }

        return $this->details($module);
    }

    public function update(UpdateModuleData $data): ModuleDetails
    {
        $audiences = $this->audiences($data->audiences);
        $allowedScopes = $this->allowedScopes($data->allowedScopes);

        return $this->transactions->run(function () use ($data, $audiences, $allowedScopes): ModuleDetails {
            $module = $this->findForUpdate($data->moduleId);
            $before = $this->snapshotFromStore($module);
            $now = $this->clock->now();
            $module->update($data->name, $data->description, $now);
            $this->modules->update($module);
            $this->modules->replaceProtocolMetadata($module->id(), $audiences, $allowedScopes, $now);
            $after = $this->snapshot($module, $audiences, $allowedScopes);
            $this->changes->record(
                targetType: 'module',
                targetId: $module->id(),
                moduleId: $module->id(),
                organizationId: null,
                actor: $data->actor,
                correlation: $data->correlation,
                action: 'module.updated',
                eventName: 'module.updated.v1',
                occurredAt: $now,
                before: $before,
                after: $after,
                eventPayload: $after,
            );

            return $this->details($module);
        });
    }

    public function deactivate(ModuleLifecycleData $data): ModuleDetails
    {
        return $this->changeLifecycle($data, 'deactivate');
    }

    public function reactivate(ModuleLifecycleData $data): ModuleDetails
    {
        return $this->changeLifecycle($data, 'reactivate');
    }

    public function softDelete(ModuleLifecycleData $data): ModuleDetails
    {
        return $this->changeLifecycle($data, 'soft_delete');
    }

    public function restore(ModuleLifecycleData $data): ModuleDetails
    {
        return $this->changeLifecycle($data, 'restore');
    }

    private function changeLifecycle(ModuleLifecycleData $data, string $operation): ModuleDetails
    {
        return $this->transactions->run(function () use ($data, $operation): ModuleDetails {
            $module = $this->findForUpdate($data->moduleId);
            $before = $this->snapshotFromStore($module);
            $now = $this->clock->now();

            if ($operation === 'deactivate') {
                $module->deactivate($now);
            } elseif ($operation === 'reactivate') {
                $module->reactivate($now);
            } elseif ($operation === 'soft_delete') {
                $module->softDelete($now);
            } else {
                $module->restore($now);
            }

            $this->modules->update($module);
            $after = $this->snapshotFromStore($module);
            $this->changes->record(
                targetType: 'module',
                targetId: $module->id(),
                moduleId: $module->id(),
                organizationId: null,
                actor: $data->actor,
                correlation: $data->correlation,
                action: 'module.'.$operation,
                eventName: 'module.lifecycle.changed.v1',
                occurredAt: $now,
                before: $before,
                after: $after,
                metadata: ['reason' => $data->reason],
                eventPayload: $after,
            );

            return $this->details($module);
        });
    }

    private function findForUpdate(UuidV7 $moduleId): ModuleDefinition
    {
        $module = $this->modules->findById($moduleId, forUpdate: true);

        if ($module === null) {
            throw new ModuleNotFound;
        }

        return $module;
    }

    private function details(ModuleDefinition $module): ModuleDetails
    {
        return ModuleDetails::fromModule(
            module: $module,
            audiences: $this->modules->audiencesFor($module->id()),
            allowedScopes: $this->modules->allowedScopesFor($module->id()),
        );
    }

    /**
     * @param  list<string>  $values
     * @return list<Audience>
     */
    private function audiences(array $values): array
    {
        $audiences = [];

        foreach ($values as $value) {
            $audience = new Audience($value);
            $audiences[$audience->value] = $audience;
        }

        ksort($audiences);

        return array_values($audiences);
    }

    /**
     * @param  list<string>  $values
     * @return list<AllowedScope>
     */
    private function allowedScopes(array $values): array
    {
        $scopes = [];

        foreach ($values as $value) {
            $scope = new AllowedScope($value);
            $scopes[$scope->value] = $scope;
        }

        ksort($scopes);

        return array_values($scopes);
    }

    /**
     * @param  list<Audience>  $audiences
     * @param  list<AllowedScope>  $allowedScopes
     * @return array<string, array<int, string>|string|null>
     */
    private function snapshot(ModuleDefinition $module, array $audiences, array $allowedScopes): array
    {
        return [
            'identifier' => $module->identifier()->value,
            'name' => $module->name(),
            'description' => $module->description(),
            'status' => $module->status()->value,
            'deleted_at' => $module->deletedAt()?->format(DATE_ATOM),
            'audiences' => array_map(static fn (Audience $audience): string => $audience->value, $audiences),
            'allowed_scopes' => array_map(static fn (AllowedScope $scope): string => $scope->value, $allowedScopes),
        ];
    }

    /**
     * @return array<string, array<int, string>|string|null>
     */
    private function snapshotFromStore(ModuleDefinition $module): array
    {
        return [
            'identifier' => $module->identifier()->value,
            'name' => $module->name(),
            'description' => $module->description(),
            'status' => $module->status()->value,
            'deleted_at' => $module->deletedAt()?->format(DATE_ATOM),
            'audiences' => $this->modules->audiencesFor($module->id()),
            'allowed_scopes' => $this->modules->allowedScopesFor($module->id()),
        ];
    }
}
