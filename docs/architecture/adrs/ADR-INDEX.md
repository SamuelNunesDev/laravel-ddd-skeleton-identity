# Índice de Architecture Decision Records

**Projeto:** Laravel DDD Skeleton with Identity Platform  
**Versão do conjunto:** 0.1  
**Data:** 2026-07-24

Os ADRs abaixo registram decisões arquiteturais de impacto duradouro. Detalhes de implementação reversíveis permanecem no TRD.

| ADR | Decisão | Status |
|---|---|---|
| [ADR-001](ADR-001-Modular-Monolith-Hexagonal.md) | Monólito modular vertical com arquitetura hexagonal | Aceito |
| [ADR-002](ADR-002-Shared-Schema-Multitenancy.md) | Multitenancy lógico em schema compartilhado | Aceito |
| [ADR-003](ADR-003-Passport-With-OIDC-Layer.md) | Laravel Passport com camada OIDC própria | Aceito |
| [ADR-004](ADR-004-Authorization-Model.md) | Modelo de autorização por módulo, organização e delegação | Aceito |
| [ADR-005](ADR-005-Token-Session-Revocation.md) | Estratégia de tokens, sessões e revogação | Aceito |
| [ADR-006](ADR-006-Vue-Inertia-Admin-Frontend.md) | Vue e Inertia para a interface administrativa | Aceito |
| [ADR-007](ADR-007-Soft-Delete-Audit-Retention.md) | Soft delete, auditoria e política de retenção | Aceito |

## Convenção de status

- **Proposto:** decisão em análise.
- **Aceito:** decisão vigente e aplicável.
- **Substituído:** decisão trocada por ADR posterior.
- **Depreciado:** mantido para histórico, mas não recomendado.

## Regras de manutenção

1. Uma decisão aceita não deve ser reescrita para ocultar seu histórico.
2. Mudanças substanciais devem gerar novo ADR, indicando qual decisão foi substituída.
3. O TRD pode detalhar a implementação, mas não contrariar ADR aceito.
4. Links para código, migrations e testes arquiteturais podem ser acrescentados após a implementação.
