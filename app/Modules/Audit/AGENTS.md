# AGENTS.md - Audit Module

## Module responsibility

Own immutable security and administrative audit evidence.

## Module boundaries

- Own append-only audit records, actor/target/context snapshots, authorized
  queries and retention/expunge execution.
- Do not decide another module's authorization or import its Eloquent models.
- Accept audit facts through a small application contract and keep producers
  independent of Audit infrastructure.

## Mandatory invariants

- Application flows can append but never update or delete audit events.
- Passwords, full tokens, OAuth/TOTP secrets and recovery codes are redacted
  before persistence.
- Organization-scoped queries cannot expose another organization's events.
- Retention follows ADR-007 and the TRD; only eligible expired categories are
  physically expunged.
- Audit writes preserve correlation, actor, target, result and time without
  unnecessary PII.

## Integration contracts

- Expose record/query ports without leaking persistence types.
- Consume versioned audit facts from every module and publish no mutable
  business state back to producers.

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
php artisan make:port Audit ExampleRepository --type=out
php artisan make:adapter Audit EloquentExampleRepository --port=ExampleRepository --type=persistence
php artisan make:integration Audit ExternalService
php artisan ddd:check --module=Audit
```

## Important instruction for AI agents

Do not infer business rules that are not present in the user-provided context, tests or existing code.

When unsure, ask for clarification instead of creating domain behavior from assumptions.

Do not move code across module boundaries unless explicitly requested.
