# ADR-002 — Multitenancy lógico em schema compartilhado

**Status:** Aceito  
**Data:** 2026-07-24  
**Decisores:** mantenedores do projeto

## Contexto

Uma identidade pode participar de várias organizações e escolher em qual combinação usuário/organização deseja operar. Permissões podem ser compartilhadas entre organizações dentro do mesmo módulo, enquanto papéis organizacionais e diversos vínculos são restritos a uma organização.

O MVP deve manter operação simples e permitir consultas e transações entre identidade, organização, autorização e auditoria.

## Decisão

Usar PostgreSQL 18.x com um único banco e schema compartilhado:

- identidade é global;
- dados organizacionais carregam `organization_id`;
- tabelas com escopo de módulo carregam também `module_id` quando necessário;
- unicidade será expressa no escopo correto por índices compostos;
- índices parciais poderão expressar unicidade e acesso eficiente somente entre registros ativos;
- toda operação organizacional exigirá um `OrganizationContext` explícito;
- controllers, jobs, comandos e consumidores deverão construir e validar esse contexto;
- autorização e escopo serão aplicados em casos de uso e queries;
- global scopes do ORM poderão reforçar isolamento, mas não serão a única barreira;
- Row-Level Security poderá ser aplicada como defesa adicional nas tabelas mais críticas, sem substituir as validações da aplicação;
- chaves estrangeiras e políticas de banco impedirão remoção física de registros referenciados;
- auditoria registrará organização, módulo, ator, sujeito e correlação quando aplicável.

O contexto persistido após login conterá apenas a combinação identidade/organização. O módulo ativo será selecionado a cada entrada; se houver somente um módulo acessível, sua seleção será automática.

## Justificativa

Schema compartilhado atende melhor ao modelo de identidade global e reduz o custo de provisionamento, migrations, relatórios e transações do MVP. A exigência de contexto explícito torna o isolamento verificável em testes e reduz dependência de mecanismos mágicos do ORM.

## Consequências

### Positivas

- provisionamento de organização sem criar banco;
- migrations e backups unificados;
- vínculos entre organizações e identidades simples;
- menor custo operacional;
- consultas administrativas e auditoria mais diretas.

### Negativas

- um erro de filtro pode causar vazamento entre organizações;
- organizações não têm isolamento físico;
- tabelas e índices podem crescer mais rapidamente;
- futura residência regional de dados exigirá evolução arquitetural.

## Alternativas consideradas

- **Banco por organização:** maior isolamento, mas complexidade elevada para identidade global, migrations e operação.
- **Schema por organização:** oferece separação nominal, mas multiplica migrations, conexões e operação sem resolver o vínculo com identidades globais.
- **Filtro implícito apenas por global scope:** rejeitado por ser fácil de contornar em jobs, queries nativas e rotinas administrativas.

## Notas de implementação

- criar tipos imutáveis para `OrganizationId`, `ModuleId` e `OrganizationContext`;
- impedir execução de caso de uso organizacional sem contexto;
- aplicar testes negativos entre organizações em cada adapter;
- incluir `organization_id` em índices de acesso frequente;
- armazenar identificadores em `uuid`, datas em `timestamptz` e metadados apropriados em `jsonb`;
- se Row-Level Security for habilitada, definir o contexto por transação e testar conexões reutilizadas para impedir vazamento de contexto;
- exigir justificativa e auditoria reforçada para operações de suporte que atravessem organizações.
