# ModuleCatalog Module

## Purpose

Owns the module catalog and organization module enablement.

## Business capability

This module describes the systems protected by the Identity Platform and their
registered OAuth applications.

## Business boundaries

It owns modules, stable identifiers, audiences, allowed scopes, organization
enablement and OAuth Client registration/configuration. This follows the TRD,
which places OAuth Clients in the module catalog.

It does not execute OAuth/OIDC protocols or issue tokens; the `OAuth` module
consumes its client and module contracts. It also does not own permissions or
roles, which belong to `AccessControl`.

## Main domain concepts

- Module
- Audience
- AllowedScope
- OrganizationModule
- OAuthClient
- RedirectUri

## Implemented application contracts

- `ManageModules`: registers, reads and updates stable module metadata,
  audiences and allowed scopes, and manages module lifecycle.
- `ManageOrganizationModules`: enables or disables modules through a validated
  organization context and lists both sides of active enablements.
- `OrganizationModuleAvailabilityAdapter`: provides Organization with active
  module and enablement facts without exposing catalog persistence.

Module identifiers are immutable and globally unique. Audiences and allowed
scopes are normalized relational records; removed values are retired rather
than physically deleted. Wildcard scope `*` is forbidden.

Organization-module enablement uses validity rows. Disabling preserves history,
and re-enabling creates a new period. A disabled or soft-deleted module is
excluded even when an older enablement row remains active.

Module, audience, scope and enablement tables reject hard delete. State,
organization/module-scoped audit and versioned outbox messages share one
transaction.

M3 intentionally does not implement permissions, roles or OAuth Clients.
Permissions and roles belong to M4; OAuth Client registration and secrets
belong to M7. Administrative HTTP endpoints remain deferred until M4 provides
server-side authorization.

## Architecture

This module follows Laravel DDD Toolkit conventions:

```text
ModuleCatalog/
|-- Domain/
|-- Application/
|   `-- Ports/
|       |-- In/
|       `-- Out/
`-- Infrastructure/
```

Ports live in `Application/Ports/In` and `Application/Ports/Out`.

Adapters live in `Infrastructure/Persistence/Adapters` and
`Infrastructure/Integrations`.

Use `app/Modules` and `make:module`. Do not reintroduce `make:domain`.

This module should preserve vertical module structure, hexagonal architecture by default, pragmatic tactical DDD and Laravel-native workflows.

## Notes

Keep this file updated when the module boundary changes.
