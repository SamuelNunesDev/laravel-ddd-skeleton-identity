# AGENTS.md - ModuleCatalog Module

## Module responsibility

Own protected module metadata, organization enablement and OAuth Client
registration.

## Module boundaries

- Own modules, stable identifiers, audiences, allowed scopes,
  organization-module links and OAuth Client configuration.
- OAuth Client ownership follows the TRD; OAuth executes protocols but does not
  own client registration.
- Do not own permissions, roles, authorization calculation or token issuance.

## Mandatory invariants

- Module identifiers are stable and unique.
- Permissions, roles, clients, audiences and scopes cannot escape their module.
- Disabled or soft-deleted modules and organization-module links cannot
  authorize new access.
- Redirect URIs use exact matching.
- Client Credentials is available only to explicitly authorized confidential
  clients; client secrets are never recoverable after display/rotation.
- Protected catalog records are never hard-deleted.

## Integration contracts

- Consume Organization context for organization-module changes.
- Provide active module, enablement, client, audience and scope contracts to
  AccessControl and OAuth.
- Publish module/client lifecycle and enablement facts.

## Architecture rules

This module follows Laravel DDD Toolkit architecture:

- vertical module structure;
- hexagonal architecture by default;
- pragmatic tactical DDD;
- Laravel-native infrastructure.

## Layer rules

### Domain

Place here:

- entities;
- value objects;
- domain events;
- domain exceptions;
- business rules.

Do not place here:

- Laravel-specific code;
- Eloquent models;
- HTTP controllers;
- form requests;
- jobs;
- listeners;
- external SDK clients;
- infrastructure adapters.

### Application

Place here:

- use cases;
- DTOs;
- inbound ports;
- outbound ports.

Application may depend on Domain.

Application must not depend on Infrastructure.

### Infrastructure

Place here:

- controllers;
- requests;
- Eloquent models;
- persistence adapters;
- integrations;
- jobs;
- listeners;
- providers.

Infrastructure may depend on Application and Domain.

## Ports and adapters

Ports live in:

```text
Application/Ports/In
Application/Ports/Out
```

Adapters live in:

```text
Infrastructure/Persistence/Adapters
Infrastructure/Integrations
```

## Commands

Useful commands for this module:

```bash
php artisan make:port ModuleCatalog ExampleRepository --type=out
php artisan make:adapter ModuleCatalog EloquentExampleRepository --port=ExampleRepository --type=persistence
php artisan make:integration ModuleCatalog ExternalService
php artisan ddd:check --module=ModuleCatalog
```

## Important instruction for AI agents

Do not infer business rules that are not present in the user-provided context, tests or existing code.

When unsure, ask for clarification instead of creating domain behavior from assumptions.

Do not move code across module boundaries unless explicitly requested.
