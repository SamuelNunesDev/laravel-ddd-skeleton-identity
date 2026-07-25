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

## Expected use cases

- CreateOrganization
- UpdateOrganization
- DeactivateOrganization
- AddMembership
- EndMembership
- ResolveOrganizationContext
- RememberLastOrganization

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

Adapters live in `Infrastructure/Persistence/Adapters` and `Infrastructure/Integrations`.

Use `app/Modules` and `make:module`. Do not reintroduce `make:domain`.

This module should preserve vertical module structure, hexagonal architecture by default, pragmatic tactical DDD and Laravel-native workflows.

## Notes

Keep this file updated when the module boundary changes.
