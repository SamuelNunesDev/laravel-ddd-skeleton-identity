# AGENTS.md - Mfa Module

## Module responsibility

Own TOTP enrollment, recovery codes and second-factor verification.

## Module boundaries

- Own MFA methods, encrypted TOTP material, recovery-code hashes, challenges and
  successful second-factor evidence.
- Do not own passwords, organization MFA policy, web sessions or tokens.
- Consume identity and organization policy through application contracts.

## Mandatory invariants

- A TOTP method is inactive until the first code is confirmed.
- TOTP secrets are encrypted and never logged or returned after enrollment.
- Recovery codes are hashed, single-use and regenerated only after
  reauthentication.
- The installation owner and globally sensitive accounts always require MFA.
- Organization-required MFA triggers enrollment or step-up and fails closed.
- Administrative reset is explicitly authorized and audited.

## Integration contracts

- Provide enrollment state and verification/step-up contracts to Session and
  OAuth.
- Publish enrollment, recovery-code use and administrative reset facts.

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
php artisan make:port Mfa ExampleRepository --type=out
php artisan make:adapter Mfa EloquentExampleRepository --port=ExampleRepository --type=persistence
php artisan make:integration Mfa ExternalService
php artisan ddd:check --module=Mfa
```

## Important instruction for AI agents

Do not infer business rules that are not present in the user-provided context, tests or existing code.

When unsure, ask for clarification instead of creating domain behavior from assumptions.

Do not move code across module boundaries unless explicitly requested.
