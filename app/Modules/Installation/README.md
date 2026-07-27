# Installation Module

## Purpose

Coordinates first-run installation without owning identity or authorization rules.

## Business capability

This module coordinates the one-time bootstrap and lifecycle of the
installation itself.

## Business boundaries

It owns installation state, explicit ownership and public presentation settings
such as display names, logos, colors, locale and time zone. It orchestrates the
first-owner flow through public application contracts.

It does not own identity credentials, MFA methods, organizations, permissions
or audit storage. Those remain in their respective modules.

## Main domain concepts

- Installation
- InstallationSettings
- InstallationOwner
- InstallationState
- BrandingSettings

## Implemented application contracts

- `InitializeInstallation`: atomically creates the singleton installation and
  its explicit first owner through the Identity module.
- `InstallationSettings`: reads public settings and allows only the current
  owner to update them.
- `RecoverInstallationOwner`: resets the owner to a new temporary credential
  through an audited server-side procedure.
- `TransferInstallationOwnership`: defines the boundary and password/MFA proof
  required by the executable M6 flow.

Initialization is idempotent and concurrency-safe on PostgreSQL. The
installation remains `pending_mfa` until M6 confirms the owner's TOTP.
`APP_NAME` provides the initial display name and fallback.

Persisted presentation data includes display, short and legal names,
institutional description, light/dark logos, favicon, primary/secondary/accent
colors, locale, time zone, public sender data, support data, terms and privacy
URLs. Infrastructure configuration and secrets are not part of this contract.

## Owner recovery

Run the command only from a real interactive terminal:

```bash
php artisan installation:recover-owner --confirm-owner-recovery
```

The new temporary password is entered twice through hidden prompts. It is
never accepted as a command option and is absent from audit and outbox
payloads.

State, append-only audit and versioned outbox messages are committed in one
transaction. M2 intentionally provides backend contracts only; the interactive
installation and administrative screens belong to later milestones.

## Architecture

This module follows Laravel DDD Toolkit conventions:

```text
Installation/
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
