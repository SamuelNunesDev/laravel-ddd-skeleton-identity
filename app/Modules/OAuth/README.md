# OAuth Module

## Purpose

Owns OAuth clients, token flows, OIDC integration, and service principals.

## Business capability

This module is the OAuth 2.0 Authorization Server and OpenID Connect provider.

## Business boundaries

It owns protocol endpoints, authorization codes, PKCE/OIDC validation, token
issuance, JWKS, UserInfo, logout and Client Credentials execution.

OAuth Client registration and module/audience configuration belong to
`ModuleCatalog`. Human authentication, MFA, sessions and effective permissions
are consumed through application contracts and are revalidated before issuance.

## Main domain concepts

- AuthorizationRequest
- AuthorizationCode
- TokenGrant
- TokenClaims
- SigningKey
- ServicePrincipal

## Expected use cases

- AuthorizeWithPkce
- ExchangeAuthorizationCode
- IssueIdToken
- IssueAccessToken
- RotateRefreshToken
- IssueClientCredentialsToken
- PublishDiscoveryDocument
- PublishJwks
- RevokeOAuthGrant

## Architecture

This module follows Laravel DDD Toolkit conventions:

```text
OAuth/
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
