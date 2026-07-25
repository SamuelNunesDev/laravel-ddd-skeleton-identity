# Session Module

## Purpose

Owns human sessions, session selection, and revocation contracts.

## Business capability

This module manages renewable authentication state for human identities.

## Business boundaries

It owns web-session records, idle/absolute expiry, session rotation, revocation,
refresh-token families and the authenticated selection of organization/module.

It does not validate passwords or MFA secrets and does not issue OAuth access or
ID tokens. Those responsibilities remain in `Identity`, `Mfa` and `OAuth`.

## Main domain concepts

- WebSession
- RefreshTokenFamily
- SessionContext
- Revocation
- SessionExpiry

## Expected use cases

- StartWebSession
- RotateWebSession
- SelectOrganization
- SelectModule
- ListOwnSessions
- RevokeOwnSession
- RevokeIdentitySessions
- RotateRefreshToken

## Architecture

This module follows Laravel DDD Toolkit conventions:

```text
Session/
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
