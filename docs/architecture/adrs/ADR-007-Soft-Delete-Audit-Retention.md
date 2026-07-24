# ADR-007 — Soft delete, auditoria e política de retenção

**Status:** Aceito  
**Data:** 2026-07-24  
**Decisores:** mantenedores do projeto

## Contexto

Identidades, organizações, módulos, clientes, permissões, papéis e vínculos participam da trilha de segurança. A exclusão física dessas entidades prejudicaria auditoria, investigação, integridade referencial e interpretação de eventos históricos.

Ao mesmo tempo, logs crescem continuamente e não devem ser preservados para sempre sem finalidade.

## Decisão

Entidades importantes não terão exclusão física por fluxos normais. Serão desativadas, encerradas ou revogadas:

- cadastros usarão `deleted_at`, `disabled_at` ou estado equivalente;
- vínculos temporais usarão `started_at` e `ended_at`;
- credenciais e sessões serão revogadas;
- chaves naturais de entidades desativadas não serão automaticamente reutilizadas quando isso gerar ambiguidade histórica;
- reativação será um caso de uso explícito e auditado;
- foreign keys usarão `RESTRICT` ou comportamento equivalente, sem cascata destrutiva sobre dados de segurança;
- eventos de auditoria serão append-only e nunca sofrerão soft delete;
- qualquer purge excepcional exigirá rotina administrativa separada, autorização de plataforma, motivo e evento de auditoria.

Política padrão de retenção:

| Categoria | Retenção online | Destino após retenção |
|---|---:|---|
| Auditoria de segurança e administração | 3 anos | Purge após janela de segurança de 30 dias |
| Logs operacionais da aplicação | 90 dias | Purge |
| Telemetria detalhada de traces | 30 dias | Purge ou agregação |
| Métricas agregadas | 13 meses | Purge |
| Backups | 90 dias | Expiração criptográfica e física |

As durações serão configuráveis por instalação, respeitando mínimos definidos pelo operador e obrigações legais. Legal hold suspenderá o purge dos registros abrangidos.

Dados pessoais poderão ser anonimizados quando houver obrigação válida, preservando identificadores técnicos mínimos e a integridade da auditoria.

## Justificativa

Três anos é um padrão sensato para auditoria do produto: cobre investigações tardias e ciclos operacionais sem impor retenção indefinida. Logs operacionais e traces têm utilidade muito mais curta e, portanto, retenções menores reduzem custo e exposição.

## Consequências

### Positivas

- histórico de segurança preservado;
- integridade referencial;
- investigação e suporte mais confiáveis;
- política de armazenamento previsível;
- redução controlada de dados antigos.

### Negativas

- crescimento contínuo das tabelas de auditoria até o purge;
- unicidade e reativação exigem regras cuidadosas;
- pedidos de apagamento demandam anonimização, não simples remoção;
- legal hold aumenta complexidade operacional.

## Alternativas consideradas

- **Hard delete convencional:** rejeitado para entidades importantes por destruir contexto histórico.
- **Retenção indefinida:** rejeitada por custo, risco e ausência de necessidade universal.
- **Um único prazo para todos os logs:** rejeitado porque auditoria, traces, métricas e logs operacionais têm finalidades distintas.
- **Sete anos como padrão:** possível em domínios regulados, mas excessivo como padrão geral do skeleton; poderá ser configurado pela instalação.

## Notas de implementação

- particionar tabelas de auditoria por data quando o volume justificar;
- executar purge em lotes idempotentes e observáveis;
- registrar contagem, intervalo e política usada em cada execução;
- não incluir segredos, senhas, tokens ou dados pessoais desnecessários nos eventos;
- testar reativação, colisão de chaves, legal hold e purge;
- documentar que retenção configurável não substitui análise jurídica do sistema derivado.
