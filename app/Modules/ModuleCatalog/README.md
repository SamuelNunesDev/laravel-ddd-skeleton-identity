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

## Expected use cases

- RegisterModule
- ConfigureModuleProtocolMetadata
- EnableModuleForOrganization
- DisableModuleForOrganization
- RegisterOAuthClient
- RotateOAuthClientSecret
- RevokeOAuthClient

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

Adapters live in `Infrastructure/Persistence/Adapters` and `Infrastructure/Integrations`.

Use `app/Modules` and `make:module`. Do not reintroduce `make:domain`.

This module should preserve vertical module structure, hexagonal architecture by default, pragmatic tactical DDD and Laravel-native workflows.

## Notes

Keep this file updated when the module boundary changes.
