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

## Implemented application contracts

- `CreateIdentity`: creates a global identity and a non-recoverable temporary
  Argon2id credential.
- `IdentityDirectory`: reads identity details and updates allowed profile data.
- `ManageIdentityLifecycle`: deactivates, reactivates, soft deletes and restores
  identities.
- `ManageIdentityCredentials`: verifies credentials, resets a temporary
  credential and performs its mandatory first change.

The default temporary lifetime is 24 hours and may be configured or requested
up to 72 hours. Restoring an identity leaves it disabled; reactivation is a
separate audited operation. A non-deleted normalized e-mail is unique.

The Installation module supplies the owner-protection adapter, so its current
owner cannot be deactivated or soft deleted.

## Persistence and events

`identities` stores the profile, lifecycle timestamps,
`must_change_password` and the global `authorization_version`.
`identity_credentials` stores only password hashes and credential timestamps.
Hard delete is rejected by the database.

State, append-only audit and versioned outbox messages are committed in one
transaction. Session/token consumers are implemented in their owning
milestones. M2 intentionally exposes no administrative HTTP surface before the
authorization and panel milestones.

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

Adapters live in `Infrastructure/Persistence/Adapters`; password hashing lives
in `Infrastructure/Security`.

Use `app/Modules` and `make:module`. Do not reintroduce `make:domain`.

This module should preserve vertical module structure, hexagonal architecture by default, pragmatic tactical DDD and Laravel-native workflows.

## Notes

Keep this file updated when the module boundary changes.
