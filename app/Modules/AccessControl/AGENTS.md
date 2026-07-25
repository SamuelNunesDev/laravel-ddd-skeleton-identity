# AGENTS.md - AccessControl Module

## Module responsibility

Own contextual authorization, role assignment and controlled delegation.

## Module boundaries

- Own permissions, global/organizational roles, role composition, assignments,
  direct effects, delegation limits and effective-permission calculation.
- Do not authenticate identities, resolve raw organization input, own module
  metadata or issue tokens.
- Require validated identity, organization and module context through
  application contracts.

## Mandatory invariants

- Effective permissions are `(inherited union direct grants) minus direct
  denials`.
- A direct grant/denial requires an active role in the same context.
- Grant and denial are mutually exclusive for the same contextual permission.
- Access requires at least one active role and one effective permission.
- Roles and permissions never cross module boundaries; organizational roles
  never cross their organization.
- Auto-elevation and delegation beyond the actor's limit fail closed.
- Authorization changes are transactional and never hard-delete history.

## Integration contracts

- Consume validated Identity, Organization and ModuleCatalog contracts.
- Provide effective-permission and access-decision contracts to Session, OAuth
  and administrative HTTP adapters.
- Publish assignment, override and authorization-version changes.

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
php artisan make:port AccessControl ExampleRepository --type=out
php artisan make:adapter AccessControl EloquentExampleRepository --port=ExampleRepository --type=persistence
php artisan make:integration AccessControl ExternalService
php artisan ddd:check --module=AccessControl
```

## Important instruction for AI agents

Do not infer business rules that are not present in the user-provided context, tests or existing code.

When unsure, ask for clarification instead of creating domain behavior from assumptions.

Do not move code across module boundaries unless explicitly requested.
