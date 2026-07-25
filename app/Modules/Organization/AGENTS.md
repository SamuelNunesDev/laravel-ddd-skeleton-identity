# AGENTS.md - Organization Module

## Module responsibility

Own organizations, memberships and validated organization context.

## Module boundaries

- Own organization lifecycle, memberships, organization MFA policy and the
  identity's last-organization preference.
- Build `OrganizationContext` only after validating organization and
  membership state.
- Do not own identities, module definitions, roles or permissions.

## Mandatory invariants

- Never trust `organization_id` received from a request.
- Context requires an active identity, organization and membership.
- A preference is only a hint and must be revalidated for the current flow.
- Membership history is preserved; protected records are never hard-deleted.
- Organizational adapters have negative cross-organization tests.

## Integration contracts

- Consume Identity status and ModuleCatalog enablement contracts.
- Publish organization/membership lifecycle facts.
- Provide validated `OrganizationContext` to AccessControl, Session and OAuth.

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
php artisan make:port Organization ExampleRepository --type=out
php artisan make:adapter Organization EloquentExampleRepository --port=ExampleRepository --type=persistence
php artisan make:integration Organization ExternalService
php artisan ddd:check --module=Organization
```

## Important instruction for AI agents

Do not infer business rules that are not present in the user-provided context, tests or existing code.

When unsure, ask for clarification instead of creating domain behavior from assumptions.

Do not move code across module boundaries unless explicitly requested.
