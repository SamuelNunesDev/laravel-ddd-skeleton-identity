# ADR-003 — Laravel Passport com camada OpenID Connect própria

**Status:** Aceito  
**Data:** 2026-07-24  
**Decisores:** mantenedores do projeto

## Contexto

O produto deve funcionar como provedor de identidade para módulos internos e sistemas integrados. O MVP necessita Authorization Code com PKCE, refresh tokens, Client Credentials, descoberta OIDC, ID Token, UserInfo, JWKS e SSO.

Laravel Passport fornece um servidor OAuth 2.0 integrado ao ecossistema Laravel, mas não constitui, por si só, uma implementação completa de OpenID Connect.

## Decisão

Usar Laravel Passport como mecanismo OAuth 2.0 e implementar no módulo `OAuth` uma camada OpenID Connect compatível com OIDC Core.

Serão suportados:

- Authorization Code Flow com PKCE `S256`;
- Client Credentials para integrações máquina a máquina;
- refresh token com rotação;
- revogação de access e refresh tokens;
- endpoint de autorização e consentimento;
- discovery em `/.well-known/openid-configuration`;
- JWKS em `/.well-known/jwks.json`;
- emissão de ID Token assinado;
- endpoint `/oauth/userinfo`;
- validação de `nonce`, `state`, `redirect_uri`, `prompt`, `auth_time`, `acr` e `amr` conforme aplicável;
- claims mínimas por padrão e claims adicionais mediante scopes;
- consentimento reutilizável e revogável.

Não serão suportados:

- Resource Owner Password Credentials;
- Implicit Flow;
- PKCE `plain`;
- redirects com curingas;
- emissão de `scope=*`.

Clientes internos confiáveis poderão ter consentimento administrativo pré-aprovado, sempre com registro auditável.

## Justificativa

Passport reduz o volume e o risco de implementar primitivas OAuth, enquanto a camada própria cobre os contratos específicos de identidade. PKCE e a exclusão de grants legados alinham o projeto às práticas atuais de segurança OAuth.

## Consequências

### Positivas

- integração natural com Laravel;
- reutilização de gestão de clientes, grants, tokens e revogação;
- suporte aos fluxos exigidos no MVP;
- compatibilidade OIDC explícita e testável.

### Negativas

- a camada OIDC torna-se código de segurança mantido pelo projeto;
- atualizações do Passport podem exigir testes de compatibilidade;
- conformidade OIDC precisa ser validada continuamente;
- não equivale automaticamente a uma certificação oficial de provedor OIDC.

## Alternativas consideradas

- **Servidor de identidade externo:** robusto, porém contraria o objetivo de oferecer identidade embarcada no skeleton.
- **Implementar OAuth e OIDC do zero:** risco e custo desnecessários.
- **Passport sem OIDC:** insuficiente para SSO padronizado e ID Tokens.
- **Sanctum:** adequado a autenticação de aplicações próprias, mas não aos requisitos de servidor OAuth/OIDC.

## Notas de implementação

- publicar metadados e algoritmos realmente suportados;
- usar somente redirect URIs previamente cadastradas com comparação exata;
- manter chaves de assinatura fora do repositório;
- adicionar testes de contrato para discovery, JWKS, ID Token, nonce e UserInfo;
- realizar threat modeling dos endpoints de autorização e token;
- tratar mensagens de erro OAuth sem expor detalhes sensíveis.
