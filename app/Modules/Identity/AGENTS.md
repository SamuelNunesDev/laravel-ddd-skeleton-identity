# AGENTS.md - Identity Module

## Module responsibility

Own the global lifecycle and credentials of human identities.

## Module boundaries

- Own profile data, normalized e-mail, password credentials, temporary-password
  state and identity lifecycle.
- Do not own memberships, MFA methods, sessions, roles or OAuth tokens.
- Expose stable identifiers and application contracts; never expose the
  Eloquent model to another module.

## Mandatory invariants

- Identity is global and is not duplicated per organization.
- E-mail normalization/uniqueness is enforced consistently.
- Password material is hashed and never logged or audited.
- Temporary passwords expire, are shown only at definition time and force a
  change before OAuth continuation.
- Deactivation or soft delete blocks authentication and triggers session/token
  revocation through contracts; hard delete is forbidden.

## Integration contracts

- Publish identity-disabled, identity-restored and credential-changed facts.
- Provide active-identity and credential-verification application contracts to
  Installation, Organization, Session and OAuth.

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
php artisan make:port Identity ExampleRepository --type=out
php artisan make:adapter Identity EloquentExampleRepository --port=ExampleRepository --type=persistence
php artisan make:integration Identity ExternalService
php artisan ddd:check --module=Identity
```

## Important instruction for AI agents

Do not infer business rules that are not present in the user-provided context, tests or existing code.

When unsure, ask for clarification instead of creating domain behavior from assumptions.

Do not move code across module boundaries unless explicitly requested.
