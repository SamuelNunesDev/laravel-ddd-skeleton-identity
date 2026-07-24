# ADR-005 — Estratégia de tokens, sessões e revogação

**Status:** Aceito  
**Data:** 2026-07-24  
**Decisores:** mantenedores do projeto

## Contexto

JWTs permitem validação distribuída, mas permanecem válidos até expirar se APIs consumidoras não consultarem o emissor. O produto precisa equilibrar desempenho, SSO, revogação, alterações rápidas de autorização e segurança de integrações.

## Decisão

Adotar tokens JWT assinados com RS256 e chave identificada por `kid`, publicando chaves ativas por JWKS.

Tempos padrão:

| Artefato | Validade |
|---|---:|
| Authorization code | 60 segundos |
| Access token humano | 10 minutos |
| ID Token | 5 minutos |
| Access token Client Credentials | 5 minutos |
| Refresh token | 30 dias |

Regras adicionais:

- refresh tokens serão rotacionados a cada uso;
- reutilização de refresh token rotacionado revogará a família correspondente;
- logout revogará sessão, grants associados e refresh tokens aplicáveis;
- access tokens carregarão `jti`, `sub`, `aud`, `iss`, `iat`, `exp`, organização, módulo, scopes e versão de autorização quando aplicável;
- cada mudança relevante de acesso incrementará `authz_version`;
- APIs internas críticas compararão `authz_version` ou usarão introspecção/cache de revogação;
- JTIs revogados permanecerão no Redis até a expiração natural do token;
- rotação de chave manterá chaves públicas antigas enquanto houver tokens não expirados;
- refresh tokens, segredos de cliente e códigos de recuperação nunca serão armazenados em texto puro;
- cookies de sessão web serão `Secure`, `HttpOnly` e `SameSite` apropriado ao fluxo.

## Justificativa

Access tokens curtos limitam a janela de abuso. Refresh rotation permite sessões utilizáveis sem tokens de acesso longos. `authz_version` e revogação por JTI cobrem operações sensíveis nas quais esperar a expiração não é suficiente.

## Consequências

### Positivas

- validação local eficiente;
- janela curta após comprometimento;
- revogação efetiva de sessões renováveis;
- rotação de chaves sem indisponibilidade;
- mudanças de autorização detectáveis antes da expiração em APIs críticas.

### Negativas

- APIs precisam implementar políticas coerentes de validação;
- revogação imediata de JWT exige consulta adicional ou cache;
- rotação de refresh aumenta estado e complexidade;
- indisponibilidade do Redis pode degradar verificações reforçadas.

## Alternativas consideradas

- **Tokens opacos com introspecção obrigatória:** revogação simples, mas acopla cada requisição à disponibilidade do emissor.
- **JWT de longa duração:** operacionalmente simples, porém amplia risco.
- **Revogação somente por expiração:** insuficiente para desligamento, comprometimento e mudança de acesso.
- **Algoritmo simétrico:** rejeitado porque distribuir o segredo de validação também permite assinar tokens.

## Notas de implementação

- definir política explícita de fail-closed para rotas críticas quando a verificação de revogação estiver indisponível;
- automatizar rotação de chave com sobreposição;
- nunca registrar tokens completos;
- testar replay de refresh token, logout global, troca de papel e rotação de chave;
- permitir configuração de tempos dentro de limites seguros, sem enfraquecer silenciosamente os padrões.
