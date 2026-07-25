# Identity Module

## Purpose

Owns global human identity lifecycle and identity-level contracts.

## Business capability

This module manages the global record and credentials of a human identity.

## Business boundaries

It owns identity profile data, normalized e-mail uniqueness, password
credentials, temporary-password state and the active/deactivated/soft-deleted
lifecycle.

It does not own memberships, roles, MFA methods, web sessions or OAuth tokens.
Other modules refer to an identity through application contracts and stable
identifiers, never through its Eloquent model.

## Main domain concepts

- Identity
- EmailAddress
- PasswordCredential
- TemporaryPassword
- IdentityStatus

## Expected use cases

- CreateIdentity
- UpdateIdentity
- DisableIdentity
- SoftDeleteIdentity
- RestoreIdentity
- DefineTemporaryPassword
- ChangePassword
- GetIdentityDetails

## Architecture

This module follows Laravel DDD Toolkit conventions:

```text
Identity/
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
