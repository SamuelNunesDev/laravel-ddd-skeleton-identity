# AGENTS.md - Session Module

## Module responsibility

Own renewable human authentication sessions, context selection and revocation.

## Module boundaries

- Own web sessions, idle/absolute expiration, rotation, revocation,
  refresh-token families and authenticated organization/module selection.
- Do not validate password/TOTP secrets or issue access/ID tokens.
- Consume Identity, Organization, AccessControl and Mfa contracts.

## Mandatory invariants

- Rotate the session identifier after login and step-up.
- Enforce both idle and absolute expiration.
- Disabled identities, organizations or memberships terminate applicable
  renewable access.
- Context selection is server-validated; request identifiers are never trusted.
- Refresh-token rotation detects reuse and revokes the affected family.
- Revocation data and security failures follow the TRD fail-closed policy.

## Integration contracts

- Provide authenticated-session/context and revocation contracts to OAuth and
  HTTP middleware.
- Publish login, context-change, refresh-reuse and revocation facts.

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
php artisan make:port Session ExampleRepository --type=out
php artisan make:adapter Session EloquentExampleRepository --port=ExampleRepository --type=persistence
php artisan make:integration Session ExternalService
php artisan ddd:check --module=Session
```

## Important instruction for AI agents

Do not infer business rules that are not present in the user-provided context, tests or existing code.

When unsure, ask for clarification instead of creating domain behavior from assumptions.

Do not move code across module boundaries unless explicitly requested.
