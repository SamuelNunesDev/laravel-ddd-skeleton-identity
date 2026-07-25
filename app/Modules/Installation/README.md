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

## Expected use cases

- InitializeInstallation
- RegisterInstallationOwner
- TransferInstallationOwnership
- UpdateInstallationSettings
- GetPublicInstallationSettings

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
