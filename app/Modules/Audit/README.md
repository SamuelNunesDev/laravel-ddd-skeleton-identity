# Audit Module

## Purpose

Owns append-only security and administrative audit events.

## Business capability

This module provides immutable evidence of security and administrative events.

## Business boundaries

It owns append-only audit records, redacted actor/target/context snapshots,
authorized queries and retention/expunge policy for audit data.

It does not decide whether another module's action is allowed and must not
become a shared business model. Producing modules publish audit facts through a
small application contract without depending on Audit infrastructure.

## Main domain concepts

- AuditEvent
- AuditActor
- AuditTarget
- AuditContext
- AuditOutcome
- RetentionPeriod

## Expected use cases

- RecordAuditEvent
- SearchGlobalAudit
- SearchOrganizationAudit
- ExportAuthorizedAudit
- ExpungeExpiredAuditData

## Architecture

This module follows Laravel DDD Toolkit conventions:

```text
Audit/
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
