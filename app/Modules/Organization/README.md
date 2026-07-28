# Organization Module

## Purpose

Owns organizations, memberships, and validated organization context.

## Business capability

This module manages tenants and the relationship between global identities and
those tenants.

## Business boundaries

It owns organizations, memberships, organization MFA policy, the identity's
last-organization preference and the validated `OrganizationContext`.

It does not own identities, the module catalog, roles or permission
calculation. A request-provided organization identifier is only input to
validation and is never trusted as context.

## Main domain concepts

- Organization
- Membership
- OrganizationContext
- OrganizationPreference
- MfaPolicy

## Implemented application contracts

- `ManageOrganizations`: creates, reads, updates, deactivates, reactivates,
  soft deletes and restores organizations.
- `ManageMemberships`: creates and ends memberships and lists their preserved
  history inside a validated context.
- `ResolveOrganizationContext`: creates an immutable context only after
  validating the current identity, organization, membership and optional
  module enablement.
- `OrganizationSelection`: lists structurally applicable organizations and
  modules, stores one organization preference per identity and revalidates that
  preference before reuse.

Organization and membership identifiers use UUID v7. Organization identifiers
are immutable and globally unique. MFA policy is either `required`
(`OBRIGATÓRIO`) or `optional` (`OPCIONAL`).

Ending a membership preserves its original validity period; a later admission
creates a new row. Restoring an organization leaves it disabled until an
explicit reactivation. Protected organization and membership rows reject hard
delete at the database boundary.

## Context and module boundary

`OrganizationContext` carries identity, organization, optional module, source
and the current global authorization version. A request-provided identifier is
never itself a context.

The ModuleCatalog module implements Organization's outbound module-availability
port. Organization never queries catalog tables. M3 selection is intentionally
structural: M4 will additionally require active roles and effective
permissions before treating a module as accessible.

All changes are audited with organization scope and publish versioned outbox
messages in the same transaction. Administrative HTTP endpoints remain
deferred until M4 can authorize them on the server.

## Architecture

This module follows Laravel DDD Toolkit conventions:

```text
Organization/
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
