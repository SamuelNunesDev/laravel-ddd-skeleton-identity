# Shared Kernel

`app/Shared` contains only technical primitives that have the same meaning in
every module.

## Public primitives

- `UuidV7`: canonical UUID v7 value object. Generation is provided through the
  `UuidGenerator` application contract so Domain code does not depend on
  Laravel.
- `Clock`: UTC time source with a replaceable application contract.
- `PageRequest` and `PageResult`: bounded, one-based pagination values.
- `RequestId`, `TraceId` and `CorrelationContext`: validated request and trace
  correlation.
- `TransactionManager`: application-level transaction boundary implemented by
  Laravel infrastructure.

Business concepts must remain in their owning module. Adding a class here
requires the concept to be truly cross-cutting and semantically identical for
all consumers.

HTTP correlation is implemented by global middleware. It accepts only a UUID
v7 request ID and a valid non-zero W3C trace ID; invalid untrusted headers are
replaced.

Sensitive operations that require atomic audit use `TransactionManager` to
wrap both the business state change and the Audit module's
`RecordAuditEvent::record()` call.
