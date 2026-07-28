<?php

declare(strict_types=1);

namespace App\Modules\ModuleCatalog\Application\UseCases;

use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Modules\ModuleCatalog\Application\DTO\ChangeOrganizationModuleData;
use App\Modules\ModuleCatalog\Application\DTO\ModuleDetails;
use App\Modules\ModuleCatalog\Application\DTO\OrganizationModuleDetails;
use App\Modules\ModuleCatalog\Application\Ports\In\ManageOrganizationModules;
use App\Modules\ModuleCatalog\Application\Ports\Out\ModuleStore;
use App\Modules\ModuleCatalog\Application\Ports\Out\OrganizationModuleStore;
use App\Modules\ModuleCatalog\Application\Services\ModuleCatalogChangeRecorder;
use App\Modules\ModuleCatalog\Domain\Entities\ModuleDefinition;
use App\Modules\ModuleCatalog\Domain\Entities\OrganizationModule;
use App\Modules\ModuleCatalog\Domain\Exceptions\ModuleNotFound;
use App\Modules\ModuleCatalog\Domain\Exceptions\ModuleUnavailable;
use App\Modules\ModuleCatalog\Domain\Exceptions\OrganizationContextMismatch;
use App\Modules\ModuleCatalog\Domain\Exceptions\OrganizationModuleAlreadyEnabled;
use App\Modules\ModuleCatalog\Domain\Exceptions\OrganizationModuleNotEnabled;
use App\Modules\Organization\Application\DTO\ResolveOrganizationContextData;
use App\Modules\Organization\Application\Ports\In\ResolveOrganizationContext;
use App\Modules\Organization\Domain\ValueObjects\OrganizationContext;
use App\Shared\Application\Contracts\Clock;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Application\Contracts\UuidGenerator;
use App\Shared\Domain\ValueObjects\UuidV7;

final readonly class ManageOrganizationModulesHandler implements ManageOrganizationModules
{
    public function __construct(
        private ModuleStore $modules,
        private OrganizationModuleStore $organizationModules,
        private ResolveOrganizationContext $contextResolver,
        private ModuleCatalogChangeRecorder $changes,
        private UuidGenerator $uuidGenerator,
        private Clock $clock,
        private TransactionManager $transactions,
    ) {}

    public function enable(ChangeOrganizationModuleData $data): OrganizationModuleDetails
    {
        return $this->transactions->run(function () use ($data): OrganizationModuleDetails {
            $this->validateContext($data->context);
            $module = $this->modules->findById($data->moduleId, forUpdate: true);

            if ($module === null) {
                throw new ModuleNotFound;
            }

            if (! $module->isOperational()) {
                throw new ModuleUnavailable;
            }

            if ($this->organizationModules->findEnabled(
                $data->context->organizationId,
                $data->moduleId,
                forUpdate: true,
            ) !== null) {
                throw new OrganizationModuleAlreadyEnabled;
            }

            $now = $this->clock->now();
            $organizationModule = OrganizationModule::enable(
                id: $this->uuidGenerator->generate(),
                organizationId: $data->context->organizationId,
                moduleId: $data->moduleId,
                now: $now,
            );
            $this->organizationModules->insert($organizationModule);
            $this->record($organizationModule, $data, 'organization_module.enabled', $now);

            return OrganizationModuleDetails::fromOrganizationModule($organizationModule);
        });
    }

    public function disable(ChangeOrganizationModuleData $data): OrganizationModuleDetails
    {
        return $this->transactions->run(function () use ($data): OrganizationModuleDetails {
            $this->validateContext($data->context);
            $organizationModule = $this->organizationModules->findEnabled(
                $data->context->organizationId,
                $data->moduleId,
                forUpdate: true,
            );

            if ($organizationModule === null) {
                throw new OrganizationModuleNotEnabled;
            }

            $now = $this->clock->now();
            $organizationModule->disable($now);
            $this->organizationModules->update($organizationModule);
            $this->record($organizationModule, $data, 'organization_module.disabled', $now);

            return OrganizationModuleDetails::fromOrganizationModule($organizationModule);
        });
    }

    public function enabledForOrganization(OrganizationContext $context): array
    {
        $this->validateContext($context);
        $modules = $this->modules->findOperationalByIds(
            $this->organizationModules->enabledModuleIdsFor($context->organizationId),
        );

        return array_map(
            fn (ModuleDefinition $module): ModuleDetails => ModuleDetails::fromModule(
                module: $module,
                audiences: $this->modules->audiencesFor($module->id()),
                allowedScopes: $this->modules->allowedScopesFor($module->id()),
            ),
            $modules,
        );
    }

    public function enabledOrganizationIdsFor(UuidV7 $moduleId): array
    {
        $module = $this->modules->findById($moduleId);

        if ($module === null || ! $module->isOperational()) {
            return [];
        }

        return array_map(
            static fn (UuidV7 $id): string => $id->toString(),
            $this->organizationModules->enabledOrganizationIdsFor($moduleId),
        );
    }

    private function validateContext(OrganizationContext $context): void
    {
        if ($context->moduleId !== null) {
            throw new OrganizationContextMismatch;
        }

        $validated = $this->contextResolver->resolve(new ResolveOrganizationContextData(
            identityId: $context->identityId,
            organizationId: $context->organizationId,
            moduleId: null,
            source: $context->source,
        ));

        if ($validated->authorizationVersion !== $context->authorizationVersion) {
            throw new OrganizationContextMismatch;
        }
    }

    private function record(
        OrganizationModule $organizationModule,
        ChangeOrganizationModuleData $data,
        string $action,
        \DateTimeImmutable $occurredAt,
    ): void {
        $snapshot = [
            'organization_id' => $organizationModule->organizationId()->toString(),
            'module_id' => $organizationModule->moduleId()->toString(),
            'status' => $organizationModule->status()->value,
            'enabled_at' => $organizationModule->enabledAt()->format(DATE_ATOM),
            'disabled_at' => $organizationModule->disabledAt()?->format(DATE_ATOM),
        ];
        $this->changes->record(
            targetType: 'organization_module',
            targetId: $organizationModule->id(),
            moduleId: $organizationModule->moduleId(),
            organizationId: $organizationModule->organizationId(),
            actor: AuditActor::identity($data->context->identityId),
            correlation: $data->correlation,
            action: $action,
            eventName: 'organization.module.changed.v1',
            occurredAt: $occurredAt,
            after: $snapshot,
            metadata: ['reason' => $data->reason],
            eventPayload: $snapshot,
        );
    }
}
