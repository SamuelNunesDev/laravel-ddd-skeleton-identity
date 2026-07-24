# AGENTS.md - OAuth Module

## Module responsibility

This module represents the `OAuth` business capability.

## User-provided context

Owns OAuth clients, token flows, OIDC integration, and service principals.

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
php artisan make:port OAuth ExampleRepository --type=out
php artisan make:adapter OAuth EloquentExampleRepository --port=ExampleRepository --type=persistence
php artisan make:integration OAuth ExternalService
php artisan ddd:check --module=OAuth
```

## Important instruction for AI agents

Do not infer business rules that are not present in the user-provided context, tests or existing code.

When unsure, ask for clarification instead of creating domain behavior from assumptions.

Do not move code across module boundaries unless explicitly requested.
