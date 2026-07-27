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
- AuditOutcome
- AuditSensitivity
- CorrelationContext

## Public application contract

Producers depend only on
`App\Modules\Audit\Application\Ports\In\RecordAuditEvent` and submit an
`AuditEventData`. They must not import the PostgreSQL adapter or query the
`audit_events` table.

```php
use App\Modules\Audit\Application\DTO\AuditEventData;
use App\Modules\Audit\Application\Ports\In\RecordAuditEvent;
use App\Modules\Audit\Domain\ValueObjects\AuditActor;
use App\Modules\Audit\Domain\ValueObjects\AuditOutcome;
use App\Modules\Audit\Domain\ValueObjects\AuditSensitivity;

$eventId = $recorder->record(new AuditEventData(
    action: 'identity.created',
    actor: AuditActor::system(),
    outcome: AuditOutcome::Succeeded,
    sensitivity: AuditSensitivity::Sensitive,
    correlation: $correlation,
));
```

The action is a stable lowercase identifier. Actor types are `identity`,
`oauth_client` and `system`. Every event explicitly declares whether it is
`sensitive` or `non_sensitive`.

## Persistence and failure policy

- `audit_events` is append-only.
- The application contract and persistence port expose append only.
- PostgreSQL rejects direct `UPDATE` and `DELETE` through a database trigger.
- Redaction runs before persistence for passwords, tokens, secrets, TOTP data,
  recovery codes and authorization material.
- M1 records every event synchronously. Storage failures propagate to the
  caller, allowing sensitive operations to fail closed.
- A sensitive use case wraps its state change and `RecordAuditEvent::record()`
  in the shared `TransactionManager`; both writes then commit or roll back
  together on the same database connection.
- An outbox is intentionally not introduced in M1 because there is no external
  delivery effect. Add one when a concrete integration event requires reliable
  publication after commit.

Retention, authorized queries, exports, legal hold and controlled purge remain
scheduled for later milestones. The future purge path must be separate and
must replace or securely bypass the M1 mutation guard under ADR-007.

## HTTP correlation

Global middleware validates or generates a UUID v7 `X-Request-ID`, accepts a
W3C trace ID from `traceparent` (or `X-Trace-ID`), and returns both identifiers
in response headers. Invalid untrusted values are replaced rather than
propagated. HTTP producers receive the resulting `CorrelationContext` through
the request attributes or the application container.

## Implemented use cases

- RecordAuditEvent

The search, export, retention and expunge use cases are intentionally deferred
to their planned milestones.

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
