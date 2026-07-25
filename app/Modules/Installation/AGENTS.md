# AGENTS.md - Installation Module

## Module responsibility

Coordinate installation bootstrap, explicit ownership and public presentation
settings.

## Module boundaries

- Own installation state, owner reference and branding/settings.
- Orchestrate owner creation through Identity contracts.
- Do not persist passwords, identity profiles, MFA methods, permissions or
  audit records.
- Do not place liveness/readiness endpoints here; they are operational
  application infrastructure, not installation behavior.

## Mandatory invariants

- The owner is explicit and never inferred from a numeric ID.
- Installation initialization is concurrency-safe and cannot create two owners.
- Ownership transfer requires reauthentication, MFA and audit evidence.
- Persist only public presentation settings; infrastructure secrets stay in the
  environment.

## Integration contracts

- Consume Identity, Mfa and Audit application contracts.
- Publish installation-initialized, ownership-transferred and
  settings-updated facts without exposing Infrastructure classes.

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
php artisan make:port Installation ExampleRepository --type=out
php artisan make:adapter Installation EloquentExampleRepository --port=ExampleRepository --type=persistence
php artisan make:integration Installation ExternalService
php artisan ddd:check --module=Installation
```

## Important instruction for AI agents

Do not infer business rules that are not present in the user-provided context, tests or existing code.

When unsure, ask for clarification instead of creating domain behavior from assumptions.

Do not move code across module boundaries unless explicitly requested.
