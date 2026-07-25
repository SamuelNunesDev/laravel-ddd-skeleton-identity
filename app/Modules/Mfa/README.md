# Mfa Module

## Purpose

Owns multi-factor authentication enrollment and verification.

## Business capability

This module manages second-factor enrollment and verification for human
identities.

## Business boundaries

It owns TOTP methods, encrypted TOTP secret material, recovery-code hashes,
verification and step-up evidence.

It does not own passwords, organization policy or session persistence. It
consumes identity and policy contracts and reports successful authentication
evidence to the session/OAuth flows.

## Main domain concepts

- MfaMethod
- TotpSecret
- RecoveryCode
- MfaChallenge
- AuthenticationMethodReference

## Expected use cases

- BeginTotpEnrollment
- ConfirmTotpEnrollment
- VerifyTotp
- ConsumeRecoveryCode
- RegenerateRecoveryCodes
- ResetMfaMethod
- RequireStepUp

## Architecture

This module follows Laravel DDD Toolkit conventions:

```text
Mfa/
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
