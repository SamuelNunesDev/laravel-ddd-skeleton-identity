# AccessControl Module

## Purpose

Owns scoped roles, permissions, assignments, and direct effects.

## Business capability

This module owns contextual authorization and controlled delegation.

## Business boundaries

It owns permission catalogs, global and organizational roles, role
compositions, assignments, direct grants/denials, delegation limits and
effective-permission calculation.

It does not authenticate identities, resolve raw organization input or issue
tokens. It receives validated identity, organization and module context through
application contracts.

## Main domain concepts

- Permission
- Role
- RoleComposition
- RoleAssignment
- PermissionOverride
- EffectivePermissions
- DelegationLimit

## Expected use cases

- CreatePermission
- CreateRole
- AssignRoles
- ChangeDirectPermissionEffect
- ActivateModuleAccess
- ResolveEffectivePermissions
- RevokeModuleAccess

## Architecture

This module follows Laravel DDD Toolkit conventions:

```text
AccessControl/
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
