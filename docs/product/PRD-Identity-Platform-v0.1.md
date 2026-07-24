# PRD — Laravel DDD Skeleton with Identity Platform

**Versão:** 0.1  
**Status:** Rascunho para validação  
**Data:** 24 de julho de 2026  
**Nome do projeto:** Laravel DDD Skeleton with Identity Platform  
**Licença:** MIT  

---

## 1. Resumo executivo

O Laravel DDD Skeleton with Identity Platform será um projeto-base open source e clonável para iniciar o desenvolvimento de sistemas modulares em Laravel. O skeleton entregará previamente a estrutura arquitetural baseada em DDD, a gestão de identidades, organizações, módulos, papéis e permissões e os fluxos de autenticação e autorização, permitindo que cada novo projeto concentre seu desenvolvimento inicial nos módulos de negócio.

Por exemplo, um sistema de testes e observabilidade baseado em OpenTelemetry poderá ser iniciado a partir de um clone deste projeto, aproveitando autenticação, multi-tenancy, controle de acesso, auditoria e integração OAuth/OIDC já implementados, enquanto seus próprios módulos de coleta, análise e diagnóstico são desenvolvidos sobre a estrutura existente.

Cada módulo representará um sistema ou domínio funcional criado dentro da instalação, como Vendas, Comercial, ERP Financeiro, Analytics ou Observabilidade. A Identity Platform embutida funcionará como provedora de Single Sign-On (SSO) e autorização para esses módulos, permitindo que um usuário se autentique uma única vez e acesse diferentes capacidades dentro das organizações às quais pertence.

O sistema adotará OAuth 2.0 como protocolo de autorização e OpenID Connect (OIDC) como camada padronizada de autenticação. Cada módulo integrado poderá confiar na identidade autenticada pelo Identity Platform e receber apenas as autorizações aplicáveis à combinação entre usuário, organização e módulo.

O produto será multi-tenant: uma identidade será global, poderá pertencer a várias organizações e poderá possuir papéis e permissões diferentes em cada uma delas. O próprio painel administrativo do Identity Platform será protegido pelo mesmo catálogo de permissões utilizado para controlar os demais sistemas.

O primeiro usuário criado durante a instalação será registrado explicitamente como proprietário da instalação. Essa condição não dependerá do valor numérico de seu ID.

O nome apresentado ao usuário e a identidade visual de cada instalação serão personalizáveis. O `APP_NAME` servirá como valor inicial e fallback, enquanto as configurações persistidas da instalação definirão o nome exibido, nomes curto e institucional, logotipos, favicon, cores, idioma, fuso horário e dados públicos de suporte.

---

## 2. Problema

Empresas que operam vários sistemas frequentemente mantêm autenticação, usuários e permissões separadamente em cada aplicação. Isso gera problemas como:

- repetição de usuários e credenciais;
- dificuldade para bloquear centralmente uma identidade;
- regras de acesso inconsistentes entre sistemas;
- implementação repetida de login e MFA;
- falta de uma visão consolidada dos acessos de cada pessoa;
- dificuldade para administrar acessos diferentes por organização;
- aumento da superfície de exposição de senhas;
- ausência de auditoria centralizada;
- integrações proprietárias e pouco interoperáveis.

Além disso, um mesmo usuário pode trabalhar com diferentes empresas ou clientes e precisar de autorizações distintas em cada uma delas. Uma permissão global, sem contexto de organização e módulo, não representa corretamente esse cenário.

---

## 3. Visão do produto

Oferecer um skeleton open source para acelerar a criação de sistemas Laravel modulares, fornecendo uma base arquitetural DDD e uma Identity Platform pronta para uso, mantendo:

- início rápido de novos produtos por clonagem do projeto;
- criação incremental de módulos de negócio sobre a base existente;
- isolamento entre organizações;
- permissões granulares;
- experiência de SSO;
- segurança centralizada;
- interoperabilidade por padrões abertos;
- rastreabilidade de operações sensíveis;
- administração delegável sem escalada indevida de privilégios.

---

## 4. Objetivos

### 4.1 Objetivos do MVP

1. Centralizar identidades de usuários.
2. Permitir que um usuário pertença a múltiplas organizações.
3. Cadastrar e administrar módulos que representem os sistemas da empresa.
4. Manter um catálogo de permissões atômicas dentro de cada módulo.
5. Manter papéis próprios de cada módulo, formados por suas permissões.
6. Cadastrar e administrar OAuth Clients vinculados aos módulos.
7. Ativar o acesso de um usuário a um módulo, no contexto de uma organização, mediante a atribuição obrigatória de ao menos um papel daquele módulo e a existência de ao menos uma permissão efetiva.
8. Permitir sobrescritas diretas opcionais de concessão ou negação, mutuamente exclusivas por permissão, como exceção aos papéis.
9. Controlar autorizações por organização.
10. Autenticar usuários com e-mail e senha.
11. Permitir que cada organização exija ou não MFA de seus usuários.
12. Exigir MFA do proprietário da instalação e de contas com privilégios globais sensíveis.
13. Implementar SSO por OAuth 2.0 e OpenID Connect.
14. Emitir ID Tokens e Access Tokens com escopo limitado.
15. Permitir encerramento e revogação centralizada de sessões.
16. Auditar operações administrativas e eventos relevantes de autenticação.
17. Proteger o próprio painel administrativo por permissões.
18. Preservar o histórico das entidades de domínio e dos vínculos de autorização por meio de soft delete, sem exclusão física pelo produto.
19. Suportar comunicação máquina a máquina por OAuth 2.0 Client Credentials.
20. Permitir personalizar o nome e a identidade visual da instalação.
21. Entregar uma aplicação de referência que demonstre a integração OIDC e a proteção por permissões.

### 4.2 Resultados esperados

- Um usuário não precisa possuir uma senha diferente em cada sistema integrado.
- Um módulo integrado não recebe a senha do usuário.
- A remoção de um vínculo ou permissão passa a valer centralmente.
- Uma identidade pode ter acessos diferentes em organizações diferentes.
- Uma API recebe somente as permissões relevantes para seu módulo, sua audiência e a organização ativa.
- Operações administrativas sensíveis podem ser rastreadas.
- Um novo sistema pode ser iniciado por clonagem do skeleton sem reimplementar autenticação, organizações, papéis, permissões e auditoria.
- Serviços internos podem autenticar-se sem usuário humano por Client Credentials e receber tokens limitados ao módulo, à audiência e à organização autorizados.

---

## 5. Não objetivos do MVP

Os itens abaixo não fazem parte do primeiro MVP, salvo revisão posterior:

- cadastro público de usuários;
- registro dinâmico de módulos ou OAuth Clients;
- portal público para desenvolvedores externos;
- login social com Google, Microsoft, Apple ou outros provedores;
- federação com outros provedores de identidade;
- SAML;
- SCIM para provisionamento automático;
- passkeys/WebAuthn;
- autenticação por SMS;
- Device Authorization Flow para CLI, TV ou dispositivos limitados;
- OpenID Connect CIBA;
- autorização baseada em atributos complexos (ABAC);
- hierarquia de organizações;
- permissões com condições dinâmicas por registro;
- cobrança, planos ou limites comerciais;
- personalização visual completa por organização;
- suporte a organizações cadastrarem módulos ou OAuth Clients de forma autônoma;
- autenticação direta de módulos ou aplicações integradas por e-mail e senha do usuário;
- OAuth Resource Owner Password Credentials Grant.

---

## 6. Glossário

| Termo | Definição |
|---|---|
| Skeleton | Projeto-base clonável que fornece arquitetura, autenticação, autorização, multi-tenancy e recursos transversais para acelerar o desenvolvimento de novos sistemas e módulos de negócio. |
| Instalação | Implantação independente criada a partir do skeleton, com proprietário, configurações, identidade visual, organizações e módulos próprios. |
| Identidade | Registro global que representa um usuário humano. |
| Usuário | Pessoa que utiliza o sistema por meio de uma identidade. |
| Organização | Empresa ou tenant atendido. Substitui o uso de “cliente” no modelo de domínio. |
| Membership | Vínculo entre uma identidade e uma organização. |
| Módulo | Sistema ou produto da empresa integrado ao Identity Platform e administrado como uma unidade de acesso, como Vendas, Comercial, ERP Financeiro ou Analytics. Um módulo possui seu próprio catálogo de permissões, papéis e OAuth Clients. |
| Aplicação integrada | Componente de software de um módulo, como frontend, backend ou API, que utiliza OAuth/OIDC para autenticação ou autorização. |
| OAuth Client | Aplicação ou serviço cadastrado e vinculado a um módulo para participar dos fluxos OAuth/OIDC. Clients públicos utilizam PKCE sem secret; clients confidenciais podem autenticar-se com credenciais próprias. |
| Permissão | Capacidade atômica verificável pertencente a exatamente um módulo, como `sales.opportunities.view`. Pode ser reutilizada nas organizações em que o módulo estiver habilitado, independentemente de ter sido criada por um administrador global ou organizacional. |
| Papel global do módulo | Conjunto nomeado de permissões de um módulo criado no catálogo central e reutilizável nas organizações em que o módulo estiver habilitado. |
| Papel organizacional | Conjunto nomeado de permissões criado no contexto de uma organização e restrito à combinação entre organização e módulo. |
| Acesso ao módulo | Habilitação do usuário para entrar em um módulo no contexto de uma organização. Existe quando o membership, a organização e o módulo estão ativos, o módulo está habilitado para a organização, o usuário possui ao menos um papel ativo daquele módulo nessa organização e possui ao menos uma permissão efetiva no mesmo contexto. |
| Concessão direta | Sobrescrita granular que adiciona diretamente ao usuário uma permissão não recebida por seus papéis. |
| Negação direta | Sobrescrita granular que retira do usuário uma permissão herdada de seus papéis. |
| Sobrescrita direta de permissão | Exceção granular aplicada à combinação entre usuário, organização, módulo e permissão, com exatamente um dos efeitos: conceder ou negar. Os dois efeitos não podem coexistir para a mesma combinação. |
| Soft delete | Exclusão lógica que marca um registro como removido, impede seu uso operacional e preserva seus dados, relacionamentos e histórico para auditoria. Não remove fisicamente o registro do banco de dados. |
| Expurgo | Exclusão física, irreversível e automatizada de dados cuja retenção terminou. Aplica-se somente às categorias efêmeras ou de log definidas neste PRD, nunca às entidades de domínio importantes. |
| Organização ativa | Organização considerada na autenticação e autorização atuais. |
| Proprietário da instalação | Identidade responsável pela instalação, criada ou escolhida no processo inicial. |
| SSO | Single Sign-On; reutilização de uma autenticação central entre aplicações. |
| OAuth 2.0 | Protocolo usado para delegação de autorização. |
| Client Credentials | Fluxo OAuth 2.0 para autenticação máquina a máquina no qual um OAuth Client confidencial obtém Access Token em seu próprio nome, sem representar um usuário humano. |
| Identidade de serviço | Principal não humano representado por um OAuth Client em uma comunicação máquina a máquina. |
| OpenID Connect | Camada de identidade sobre OAuth 2.0 usada para autenticação e SSO. |
| ID Token | JWT assinado que descreve a autenticação e a identidade do usuário. |
| Access Token | Credencial usada para acessar uma API protegida. |
| Claim | Informação contida em um token. |

---

## 7. Atores

### 7.1 Proprietário da instalação

É a identidade registrada como proprietária da implantação.

Responsabilidades e características:

- é definido explicitamente durante a instalação;
- não depende de possuir `id = 1`;
- não pode ser removido sem transferência prévia da propriedade;
- deve utilizar MFA independentemente da política das organizações;
- pode transferir a propriedade mediante reautenticação;
- possui capacidade de recuperação por procedimento administrativo controlado;
- todas as suas operações sensíveis são auditadas.

### 7.2 Administrador global

Administra capacidades da plataforma conforme as permissões globais recebidas.

Exemplos:

- administrar organizações;
- administrar módulos;
- administrar OAuth Clients dos módulos;
- administrar o catálogo de permissões;
- administrar o catálogo de papéis;
- consultar auditoria global;
- administrar outros usuários privilegiados.

Um administrador global não recebe poderes implicitamente por seu nome de papel. Cada ação é autorizada por permissões atômicas.

### 7.3 Administrador de organização

Administra somente as organizações para as quais possui autorização.

Exemplos:

- adicionar usuários e desativar ou restaurar memberships da organização;
- criar permissões para módulos habilitados em sua organização, quando possuir autorização específica;
- criar papéis organizacionais restritos à combinação entre organização e módulo;
- atribuir papéis permitidos;
- conceder ou negar permissões diretamente dentro de seu limite delegável;
- configurar a exigência de MFA da organização;
- consultar auditoria limitada à organização.

### 7.4 Usuário

Pode:

- autenticar-se;
- configurar seu TOTP quando necessário ou permitido;
- consultar suas sessões;
- acessar módulos autorizados;
- escolher a organização quando possuir mais de uma opção aplicável;
- utilizar códigos de recuperação;
- encerrar suas próprias sessões.

### 7.5 Aplicação integrada

É um componente confiável de um módulo, cadastrado por meio de um OAuth Client administrado pela instalação.

Pode:

- iniciar um fluxo OIDC;
- receber um Authorization Code;
- trocar o código por tokens;
- validar o ID Token;
- utilizar o Access Token contra APIs autorizadas;
- solicitar encerramento de sessão, conforme os recursos suportados.

### 7.6 API protegida

É o recurso que:

- recebe o Access Token;
- valida assinatura, emissor, audiência e expiração;
- identifica a organização ativa;
- verifica permissões;
- rejeita tokens emitidos para outra audiência.

### 7.7 Serviço integrado

É uma aplicação backend, job, worker ou integração sem usuário humano que utiliza um OAuth Client confidencial.

Pode:

- autenticar-se pelo fluxo Client Credentials;
- receber um Access Token em seu próprio nome;
- atuar somente no módulo, na audiência, na organização e nas permissões de serviço previamente autorizados;
- acessar APIs protegidas sem receber ID Token ou representar uma identidade humana.

---

## 8. Princípios do produto

### 8.1 Identidade global, autorização contextual

O usuário existe uma única vez no Identity Platform. Seus acessos são avaliados dentro de uma organização e de um módulo.

### 8.2 Permissões são o contrato de autorização

Aplicações integradas, APIs, telas e policies verificam permissões, não nomes de papéis.

Um papel chamado “Administrador” não deve ser verificado diretamente pela aplicação integrada. Ele apenas agrupa permissões.

### 8.3 Menor privilégio

Tokens, papéis e concessões devem carregar somente o acesso necessário.

### 8.4 Sem escalada por delegação

Possuir permissão para atribuir acessos não significa poder conceder qualquer permissão existente.

### 8.5 Senhas permanecem no servidor de identidade

Módulos e aplicações integradas não recebem nem encaminham a senha do usuário.

### 8.6 Padrões abertos

Login e autorização devem ser interoperáveis por OAuth 2.0 e OpenID Connect.

### 8.7 Auditoria por padrão

Alterações de segurança e autorização devem gerar eventos auditáveis.

### 8.8 Preservação do histórico por padrão

Identidades, organizações, memberships, módulos, habilitações de módulos, OAuth Clients, papéis, permissões, atribuições e sobrescritas diretas não podem ser excluídos fisicamente por operações do produto. Quando houver necessidade de remoção, o sistema deve utilizar desativação ou soft delete, preservar a integridade referencial e registrar a operação na auditoria.

---

## 9. Modelo conceitual

### 9.1 Relações principais

```text
Identidade
  └── pertence a N Organizações
        └── acessa N Módulos habilitados mediante ao menos 1 Papel e 1 Permissão efetiva
              ├── recebe 1..N Papéis do módulo
              ├── possui N Sobrescritas diretas de permissão
              └── possui N Permissões efetivas
```

### 9.2 Módulos

- Um módulo representa um sistema ou produto da empresa que opera a instalação.
- A empresa que opera a instalação cadastra os módulos que deseja proteger com o Identity Platform.
- Cada módulo possui seus próprios OAuth Clients, audiences, permissões e papéis.
- Uma organização pode ter determinados módulos habilitados.
- Uma permissão pertence a um módulo.
- Uma permissão criada por administrador global ou organizacional pode ser reutilizada em qualquer organização na qual seu módulo esteja habilitado, mas nunca em outro módulo.
- Um papel global pertence a um módulo, só pode agrupar permissões desse módulo e pode ser reutilizado entre organizações.
- Um papel criado por administrador organizacional pertence à combinação entre organização e módulo e não pode ser reutilizado por outra organização ou módulo.
- Uma permissão ou um papel só pode ser atribuído dentro de uma organização na qual o módulo correspondente esteja habilitado.

Exemplo conceitual:

```text
Módulo: Vendas
  ├── OAuth Clients: sales-web, sales-api
  ├── Permissões: opportunities.view, opportunities.create, opportunities.update
  └── Papéis:
        ├── Consultor → view, create
        └── Gerente   → view, create, update
```

Uma organização com o módulo Vendas habilitado poderá atribuir os papéis Consultor e Gerente aos seus usuários, sempre dentro de seu próprio contexto organizacional.

### 9.3 Papéis e permissões

- Um papel agrupa permissões de um único módulo.
- Papéis globais do módulo podem ser reutilizados entre organizações; papéis organizacionais são exclusivos da organização e do módulo em que foram criados.
- Permissões pertencem ao módulo e podem ser reutilizadas entre organizações, inclusive quando cadastradas por administrador organizacional.
- Um usuário pode receber vários papéis dentro de uma organização e em módulos diferentes.
- A atribuição de um papel sempre possui contexto de organização e módulo.
- O acesso de um usuário a um módulo é ativado por uma ação explícita no painel administrativo, dentro do contexto de uma organização.
- Para concluir a ativação, o administrador deve selecionar obrigatoriamente ao menos um papel ativo daquele módulo, e a configuração resultante deve conceder ao usuário ao menos uma permissão efetiva.
- O acesso ao módulo exige simultaneamente ao menos um papel ativo e ao menos uma permissão efetiva na combinação entre usuário, organização e módulo, desde que o membership, a organização e o módulo também estejam ativos e o módulo esteja habilitado para a organização.
- Um usuário pode receber sobrescritas diretas de permissões de um módulo como exceção aos papéis.
- Concessões e negações diretas são opcionais durante a ativação e podem ser administradas posteriormente.
- Sobrescritas diretas não ativam nem mantêm, isoladamente, o acesso ao módulo.
- Cada sobrescrita direta possui exatamente um efeito: `CONCEDER` ou `NEGAR`.
- Para a mesma combinação entre usuário, organização, módulo e permissão, uma concessão direta e uma negação direta não podem coexistir.
- A concessão direta adiciona uma permissão que não foi herdada dos papéis.
- A negação direta retira uma permissão herdada dos papéis.
- Permissões efetivas são calculadas a partir da união das permissões herdadas dos papéis, com a aplicação das sobrescritas diretas: `CONCEDER` inclui a permissão e `NEGAR` a exclui.
- A troca entre `CONCEDER` e `NEGAR` substitui atomicamente a sobrescrita anterior, sem criar regras diretas conflitantes.
- A remoção do último papel ativo desativa o acesso do usuário ao módulo naquele contexto. Eventuais sobrescritas diretas deixam de produzir efeito e não podem manter o acesso.
- Se alterações em papéis, permissões ou sobrescritas fizerem o usuário ficar sem nenhuma permissão efetiva, o acesso ao módulo é desativado, ainda que permaneça ao menos um papel ativo atribuído.
- O acesso volta a ser reconhecido quando o usuário voltar a possuir simultaneamente ao menos um papel ativo e ao menos uma permissão efetiva, desde que as demais condições de acesso permaneçam válidas.

### 9.4 Escopo global e organizacional

O produto terá dois escopos:

- **Global:** administração da instalação, módulos, catálogos de permissões e papéis, OAuth Clients e segurança da plataforma.
- **Organizacional:** acesso e administração dentro de uma organização.

Permissões globais não devem ser obtidas por meio de uma organização comum.

### 9.5 Identidade da instalação

O nome técnico do projeto permanece Laravel DDD Skeleton with Identity Platform, mas cada clone representa uma instalação independente e pode adotar a identidade do sistema desenvolvido.

O `APP_NAME` deve fornecer o nome inicial e funcionar como fallback. Após a instalação, configurações persistidas e administráveis devem definir:

- nome exibido;
- nome curto;
- descrição institucional;
- logotipos para fundos claro e escuro;
- favicon;
- cor primária, secundária e de destaque;
- idioma e fuso horário padrão;
- nome e e-mail público do remetente;
- e-mail e URL de suporte;
- URLs de termos de uso e política de privacidade.

Credenciais, chaves de criptografia, conexão com banco, secrets e demais configurações sensíveis ou de infraestrutura não devem ser armazenadas nesse cadastro de personalização.

---

## 10. Catálogo inicial de permissões do próprio Identity Platform

O próprio Identity Platform será tratado como um módulo protegido. Nomes abaixo são exemplos para validação.

### 10.1 Organizações

```text
identity.organizations.view
identity.organizations.create
identity.organizations.update
identity.organizations.deactivate
identity.organizations.soft-delete
identity.organizations.restore
identity.organizations.manage-security
```

### 10.2 Usuários e memberships

```text
identity.users.view
identity.users.create
identity.users.update
identity.users.disable
identity.users.soft-delete
identity.users.restore
identity.memberships.create
identity.memberships.deactivate
identity.memberships.soft-delete
identity.memberships.restore
```

### 10.3 Papéis e permissões

```text
identity.roles.view
identity.roles.create
identity.roles.update
identity.roles.deactivate
identity.roles.soft-delete
identity.roles.restore
identity.roles.assign
identity.permissions.view
identity.permissions.create
identity.permissions.update
identity.permissions.deactivate
identity.permissions.soft-delete
identity.permissions.restore
identity.permissions.assign
```

### 10.4 Módulos

```text
identity.modules.view
identity.modules.create
identity.modules.update
identity.modules.deactivate
identity.modules.soft-delete
identity.modules.restore
```

### 10.5 OAuth Clients

```text
identity.oauth-clients.view
identity.oauth-clients.create
identity.oauth-clients.update
identity.oauth-clients.deactivate
identity.oauth-clients.soft-delete
identity.oauth-clients.restore
identity.oauth-clients.rotate-secret
identity.oauth-clients.manage-service-access
```

### 10.6 Configurações da instalação

```text
identity.installation-settings.view
identity.installation-settings.update
```

### 10.7 Sessões e auditoria

```text
identity.sessions.view-own
identity.sessions.revoke-own
identity.sessions.view-any
identity.sessions.revoke-any
identity.audit.view-organization
identity.audit.view-global
```

Os nomes definitivos deverão ser estáveis, documentados e tratados como contratos entre o Identity Platform e suas interfaces.

As permissões `identity.roles.create`, `identity.roles.assign`, `identity.permissions.create` e `identity.permissions.assign` podem ser delegadas em escopo organizacional. O contexto da autorização determina em quais organizações e módulos a ação pode ocorrer; possuir autorização para atribuir não implica autorização para criar.

---

## 11. Requisitos funcionais

### RF-001 — Instalação e propriedade

O sistema deve:

1. solicitar a criação do primeiro usuário no processo inicial;
2. registrar explicitamente esse usuário como proprietário;
3. impedir que o proprietário seja removido ou desativado sem transferência;
4. permitir transferência de propriedade por fluxo autenticado;
5. exigir senha e MFA para transferir a propriedade;
6. auditar criação e transferência de propriedade;
7. oferecer um procedimento administrativo de recuperação executável no servidor;
8. auditar a recuperação administrativa;
9. inicializar o nome exibido da instalação a partir de `APP_NAME`;
10. permitir ao proprietário configurar a identidade da instalação;
11. persistir somente configurações públicas e de apresentação no cadastro da instalação, mantendo secrets e configurações de infraestrutura no ambiente;
12. auditar alterações nas configurações da instalação.

### RF-002 — Gestão de identidades

O sistema deve permitir:

- criar usuários;
- visualizar usuários;
- alterar dados permitidos;
- ativar e desativar usuários;
- permitir que o administrador defina uma senha temporária ao criar o usuário;
- exigir a alteração da senha temporária no primeiro login;
- invalidar a senha temporária após o primeiro uso, após redefinição administrativa ou após seu prazo de validade;
- impedir que o administrador consulte novamente a senha temporária depois de sua definição;
- redefinir ou iniciar recuperação de senha;
- vincular uma identidade existente a novas organizações;
- impedir duplicação de identidades quando o e-mail já pertencer a um usuário existente;
- encerrar sessões de um usuário desativado;
- excluir logicamente e restaurar identidades, conforme autorização, sem excluir fisicamente seu histórico;
- impedir autenticação e autorização de identidades excluídas logicamente;
- consultar organizações e acessos de uma identidade, conforme autorização.

### RF-003 — Gestão de organizações

O sistema deve permitir:

- criar organização;
- visualizar organização;
- alterar organização;
- ativar ou desativar organização;
- configurar política de MFA;
- habilitar módulos permitidos;
- administrar memberships;
- excluir logicamente e restaurar organizações, sem excluir fisicamente seus dados ou relacionamentos;
- impedir acesso quando a organização estiver desativada ou excluída logicamente;
- preservar o histórico completo de memberships, módulos habilitados e autorizações da organização.

A exclusão física de organizações é proibida. A desativação deve ser usada para suspender temporariamente a organização; o soft delete deve ser usado quando houver intenção de removê-la da operação, sempre com preservação de auditoria e possibilidade de restauração controlada.

### RF-004 — Gestão de módulos

O sistema deve permitir:

- cadastrar módulos que representem os sistemas ou produtos da empresa que opera a instalação;
- definir nome, identificador estável e descrição;
- ativar e desativar módulos;
- definir audiences aceitas;
- definir scopes OIDC/OAuth permitidos;
- cadastrar permissões e papéis dentro de cada módulo;
- habilitar o módulo para organizações;
- excluir logicamente e restaurar módulos;
- impedir autorização quando o módulo estiver desativado ou excluído logicamente;
- auditar criação, alteração, ativação, desativação, soft delete e restauração.

Somente usuários com permissões globais adequadas podem cadastrar e administrar módulos e OAuth Clients. O catálogo de permissões pode ser ampliado por administradores organizacionais autorizados, e os papéis seguem as regras de escopo global ou organizacional.

### RF-005 — Gestão de OAuth Clients

O sistema deve permitir:

- cadastrar OAuth Clients para módulos;
- definir tipo de client;
- definir os grant types permitidos para cada client;
- cadastrar Redirect URIs exatas;
- ativar e desativar credenciais;
- excluir logicamente e restaurar OAuth Clients, sem recuperar secrets antigos;
- rotacionar secrets de clients confidenciais;
- exibir o secret somente quando necessário e de forma controlada;
- registrar data de criação e última rotação;
- auditar criação, alteração, desativação, soft delete, restauração e rotação;
- impedir Redirect URIs não cadastradas;
- permitir Client Credentials somente para clients confidenciais expressamente autorizados;
- configurar audiences, organizações e permissões de serviço permitidas para Client Credentials;
- impedir cadastro autônomo por organizações ou desenvolvedores externos.

### RF-006 — Disponibilização de módulos para organizações

O sistema deve permitir:

- habilitar módulos para organizações;
- desabilitar módulos para organizações preservando o histórico de atribuições e auditoria;
- consultar quais organizações possuem um módulo habilitado;
- consultar quais módulos estão habilitados para uma organização;
- impedir atribuições de permissões de módulos não habilitados;
- impedir atribuições de papéis de módulos não habilitados;
- impedir emissão de autorização para módulos não habilitados;
- auditar alterações.

### RF-007 — Gestão do catálogo de permissões

O sistema deve permitir:

- cadastrar permissões atômicas;
- associar cada permissão a exatamente um módulo;
- permitir que administradores globais autorizados criem permissões no catálogo do módulo;
- permitir que administradores organizacionais autorizados criem permissões para módulos habilitados em sua organização;
- disponibilizar uma permissão criada por administrador organizacional para reutilização nas demais organizações que possuam o mesmo módulo habilitado;
- impedir que uma permissão seja reutilizada ou atribuída em outro módulo;
- definir identificador estável e descrição;
- ativar e desativar permissões;
- excluir logicamente e restaurar permissões;
- impedir duplicação do identificador dentro do mesmo módulo;
- exigir autorização global para alterar identificador, desativar, excluir logicamente ou restaurar uma permissão reutilizável do módulo, evitando impacto transversal provocado a partir de uma única organização;
- impedir exclusão física de permissões, estejam elas em uso ou não;
- recalcular as permissões efetivas e reavaliar o acesso dos usuários afetados quando uma permissão for ativada, desativada ou alterada;
- auditar alterações.

Criar uma permissão no catálogo, inclusive por administrador organizacional, não implementa automaticamente a proteção no módulo consumidor. O identificador somente produzirá efeito se a aplicação integrada ou API daquele módulo souber verificá-lo e aplicar a respectiva regra. A plataforma administra o identificador e sua atribuição, mas não presume que a capacidade correspondente já foi implementada.

### RF-008 — Gestão de papéis

O sistema deve permitir:

- criar papéis;
- associar cada papel a exatamente um módulo;
- permitir que administradores globais autorizados criem papéis globais reutilizáveis entre organizações;
- permitir que administradores organizacionais autorizados criem papéis restritos à combinação entre sua organização e o módulo;
- impedir que um papel organizacional seja utilizado por outra organização ou módulo;
- definir nome, identificador estável, descrição e escopo;
- adicionar permissões aos papéis e encerrar associações por soft delete, sem apagar a composição histórica;
- excluir logicamente e restaurar papéis;
- recalcular as permissões efetivas e reavaliar o acesso dos usuários vinculados quando as permissões de um papel forem alteradas;
- impedir que um papel contenha permissões de módulos diferentes;
- atribuir papéis a usuários;
- encerrar atribuições de papéis por soft delete ou registro de término de vigência, sem apagar o vínculo histórico;
- disponibilizar, na gestão do usuário dentro de uma organização, a ação `Ativar módulo`;
- exigir a seleção de ao menos um papel do módulo para concluir a ativação;
- permitir que concessões e negações granulares sejam selecionadas opcionalmente no mesmo fluxo;
- calcular as permissões efetivas resultantes antes de concluir a ativação e exigir a existência de ao menos uma;
- validar e registrar atomicamente o papel obrigatório e as sobrescritas opcionais informadas na ativação;
- considerar o acesso ao módulo ativo somente quando houver ao menos um papel ativo e ao menos uma permissão efetiva atribuídos ao usuário naquele módulo e organização;
- desativar o acesso ao módulo quando o último papel ativo for removido ou quando o usuário ficar sem permissões efetivas;
- visualizar permissões efetivas resultantes;
- permitir atribuição somente quando o módulo estiver habilitado para a organização;
- impedir que um papel organizacional contenha permissões globais;
- impedir que um papel contenha permissões indisponíveis para seu contexto;
- auditar criação, alteração, soft delete, restauração, atribuição e encerramento de vínculos.

No MVP, administradores autorizados da instalação podem cadastrar papéis globais dentro dos módulos, reutilizáveis entre organizações. Administradores organizacionais autorizados também podem cadastrar papéis personalizados, porém esses papéis ficam restritos à combinação entre organização e módulo em que foram criados. Em todos os casos, um papel pertence a um único módulo.

### RF-009 — Sobrescritas diretas de permissão

O sistema deve permitir:

- adicionar diretamente ao usuário uma permissão não recebida por seus papéis;
- negar diretamente uma permissão herdada de seus papéis;
- registrar somente um efeito direto, `CONCEDER` ou `NEGAR`, para a mesma combinação entre usuário, organização, módulo e permissão;
- impedir a coexistência de concessão direta e negação direta para a mesma permissão no mesmo contexto;
- substituir atomicamente o efeito direto existente quando o administrador alterar a escolha entre `CONCEDER` e `NEGAR`;
- encerrar uma sobrescrita direta por soft delete ou registro de término de vigência, fazendo a permissão voltar a ser determinada somente pelos papéis sem apagar o histórico;
- identificar visualmente permissões herdadas de papéis, concedidas diretamente e negadas diretamente;
- permitir a sobrescrita somente quando o módulo da permissão estiver habilitado para a organização;
- permitir sobrescritas ativas somente quando o usuário possuir ao menos um papel ativo no mesmo módulo e organização;
- impedir que uma concessão direta, sem papel atribuído, ative ou mantenha o acesso ao módulo;
- impedir qualquer concessão ou negação direta quando o usuário não possuir ao menos um papel ativo no mesmo módulo e organização;
- recalcular a permissão efetiva quando uma sobrescrita direta for criada, alterada ou removida;
- reavaliar o acesso ao módulo após o recálculo e desativá-lo quando não restar nenhuma permissão efetiva;
- tratar sobrescritas diretas como exceções, não como forma principal de administração;
- auditar criação, alteração, encerramento e restauração.

### RF-010 — Delegação segura

O sistema deve impedir escalada de privilégios.

Um usuário autorizado a atribuir papéis, concessões ou negações diretas:

- não pode conceder permissões globais a partir de um contexto organizacional;
- não pode atuar fora das organizações que administra;
- não pode conceder permissões indisponíveis para a organização;
- não pode conceder permissões que ultrapassem seu limite delegável;
- não pode criar ou remover negações diretas fora de seu escopo administrável;
- não pode alterar o proprietário da instalação sem o fluxo específico;
- não pode conceder a si próprio uma permissão proibida pelo limite de delegação;
- não pode usar concessão direta para contornar as regras de papéis.

As capacidades de criar permissões, criar papéis, atribuir papéis e conceder ou negar permissões são independentes. Uma identidade pode receber apenas `identity.roles.assign` e `identity.permissions.assign` em determinada organização, podendo administrar acessos existentes dentro desse contexto sem poder criar novas permissões ou papéis.

O conceito definitivo de “limite delegável” será detalhado no TRD e em ADR específico após validação do comportamento esperado.

### RF-011 — Autenticação com senha

O sistema deve:

- autenticar por e-mail e senha;
- armazenar senha somente em formato de hash seguro;
- aplicar limitação de tentativas;
- permitir recuperação de senha;
- impedir login de usuário desativado;
- impedir login em organização desativada;
- registrar eventos relevantes de sucesso e falha;
- não fornecer a senha a aplicações integradas.

### RF-012 — MFA por TOTP

O sistema deve:

- permitir cadastro de TOTP;
- apresentar QR Code;
- exigir confirmação do primeiro código antes de concluir a ativação;
- gerar códigos de recuperação;
- permitir regeneração de códigos mediante reautenticação;
- permitir uso único de cada código de recuperação;
- permitir redefinição administrativa por procedimento autorizado e auditado;
- manter a credencial TOTP vinculada à identidade global;
- avaliar a exigência de MFA conforme o contexto.

Política:

1. O proprietário da instalação sempre deve utilizar MFA.
2. Contas com privilégios globais sensíveis devem utilizar MFA.
3. Cada organização define MFA como `OBRIGATÓRIO` ou `OPCIONAL` para seus usuários.
4. Se a organização ativa exigir MFA e o usuário ainda não o tiver configurado, o fluxo deve conduzi-lo à configuração.
5. Se um usuário autenticado sem MFA mudar para uma organização que o exige, o sistema deve realizar step-up authentication.
6. Se o usuário utilizou senha e TOTP, o evento de autenticação pode declarar `amr: ["pwd", "otp"]`.

### RF-013 — Determinação da organização ativa e escolha do módulo

O sistema deve:

1. armazenar centralmente a última organização válida utilizada por cada identidade, sem separar essa preferência por módulo;
2. considerar aplicável somente uma organização em que o membership e a organização estejam ativos e exista ao menos um módulo acessível;
3. quando o login tiver sido iniciado por um OAuth Client, restringir a validação às organizações nas quais o módulo desse client esteja habilitado e o usuário possua ao menos um papel ativo e uma permissão efetiva;
4. se o usuário tiver apenas uma organização aplicável, selecioná-la automaticamente;
5. se houver várias organizações aplicáveis, reutilizar a última organização válida da identidade;
6. se não existir preferência válida, solicitar que o usuário escolha uma organização;
7. depois de determinada a organização, apresentar os módulos acessíveis à identidade naquele contexto;
8. se existir apenas um módulo acessível, selecioná-lo e iniciar o login automaticamente;
9. se existirem vários módulos e nenhum OAuth Client já tiver determinado o destino, solicitar que o usuário escolha qual módulo acessar;
10. se a solicitação tiver sido iniciada diretamente por um OAuth Client, usar seu módulo como destino após validar o acesso, sem exigir seleção redundante;
11. permitir que a aplicação integrada sugira uma organização, desde que o servidor valide integralmente o contexto;
12. impedir que qualquer aplicação integrada force uma organização ou módulo não autorizado;
13. registrar a organização escolhida como última organização válida da identidade;
14. incluir a organização ativa no contexto de autorização e no Access Token.

### RF-014 — Login por OpenID Connect

O sistema deve implementar o Authorization Code Flow com PKCE.

O fluxo deve:

1. receber uma solicitação de autorização de um OAuth Client cadastrado e vinculado a um módulo;
2. validar módulo, client, Redirect URI, scopes, `state`, `nonce` e PKCE;
3. autenticar ou reconhecer a sessão do usuário;
4. determinar a organização ativa;
5. aplicar a política de MFA;
6. validar o acesso ao módulo pela existência simultânea de ao menos um papel ativo e ao menos uma permissão efetiva na combinação entre usuário, organização e módulo;
7. emitir um Authorization Code curto e de uso único;
8. trocar o código por tokens somente quando client e PKCE forem válidos;
9. emitir ID Token para solicitações OIDC;
10. emitir Access Token limitado à audiência e à organização;
11. opcionalmente emitir Refresh Token conforme política definida;
12. impedir reutilização do Authorization Code;
13. tratar os OAuth Clients do MVP como aplicações internas confiáveis e omitir a tela interativa de consentimento;
14. manter validações de client, Redirect URI, scopes, PKCE, organização, módulo e permissões mesmo sem consentimento interativo;
15. registrar a autorização concedida ao client.

### RF-015 — ID Token

O ID Token deve:

- ser um JWT assinado;
- identificar o emissor;
- identificar o usuário por `sub` estável;
- identificar o OAuth Client destinatário por `aud`;
- possuir emissão e expiração;
- carregar `nonce` quando aplicável;
- poder informar `auth_time`;
- poder informar `amr`;
- carregar somente dados de identidade permitidos pelos scopes;
- não conter senha, segredo TOTP, códigos de recuperação ou dados desnecessários;
- não ser utilizado como credencial de autorização de API.

### RF-016 — Access Token

O Access Token deve:

- ser emitido para uma audiência específica;
- representar uma organização ativa;
- identificar o módulo autorizado;
- conter somente permissões aplicáveis ao módulo, à audiência e à organização;
- possuir validade curta;
- identificar se o sujeito é uma identidade humana ou um OAuth Client;
- identificar usuário e client nos fluxos humanos, ou o próprio client no fluxo Client Credentials;
- identificar emissor e token;
- ser rejeitado por APIs de outra audiência;
- não conter permissões de todas as organizações do usuário;
- permitir que a API determine as capacidades autorizadas.

Exemplo conceitual:

```json
{
  "iss": "https://identity.example.com",
  "sub": "user-uuid",
  "aud": "sales-api",
  "client_id": "sales-web",
  "module_id": "sales",
  "organization_id": "organization-uuid",
  "permissions": [
    "sales.opportunities.view",
    "sales.opportunities.update"
  ],
  "jti": "token-uuid",
  "iat": 1784840400,
  "exp": 1784841300
}
```

### RF-017 — Descoberta e interoperabilidade OIDC

O sistema deve publicar os metadados necessários para que aplicações descubram sua configuração.

O MVP deve contemplar:

```text
/.well-known/openid-configuration
/oauth/authorize
/oauth/token
/oauth/userinfo
/oauth/jwks
```

### RF-018 — Client Credentials

O sistema deve implementar OAuth 2.0 Client Credentials no MVP para comunicação máquina a máquina.

O fluxo deve:

1. aceitar `grant_type=client_credentials` no Token Endpoint;
2. permitir o grant somente para OAuth Clients confidenciais ativos e expressamente autorizados;
3. autenticar o client com credencial armazenada e transmitida de forma segura;
4. validar se o módulo, a organização solicitada e a habilitação do módulo para a organização estão ativos;
5. exigir a organização de destino quando o client estiver autorizado em mais de uma organização e selecioná-la automaticamente quando existir apenas uma opção;
6. limitar o token à audiência, ao módulo, à organização e às permissões de serviço previamente autorizados para o client;
7. emitir Access Token cujo sujeito represente o OAuth Client, e não uma identidade humana;
8. não emitir ID Token, pois não existe usuário autenticado;
9. não emitir Refresh Token no MVP para esse grant;
10. impedir que permissões humanas sejam inferidas ou herdadas por um client;
11. auditar emissões, falhas de autenticação, revogações e uso administrativo das credenciais;
12. suportar rotação e revogação do secret sem apagar o histórico do OAuth Client.

Revogação e encerramento de sessão deverão fazer parte do desenho final de conformidade.

### RF-019 — Sessões e revogação

O sistema deve permitir:

- ao usuário consultar suas sessões;
- ao usuário encerrar suas sessões;
- a administradores autorizados revogar sessões;
- encerrar sessões de usuário desativado;
- invalidar Refresh Tokens conforme eventos de segurança;
- responder à remoção de memberships, papéis e permissões;
- registrar eventos de revogação.

O efeito de uma alteração de permissão sobre Access Tokens já emitidos deverá considerar validade curta, revogação e/ou versionamento de autorização. A estratégia será definida no TRD.

### RF-020 — Auditoria

O sistema deve registrar, no mínimo:

- criação, alteração, ativação, desativação, soft delete e restauração de usuários;
- criação, alteração, ativação, desativação, soft delete e restauração de organizações;
- alteração da política de MFA;
- criação, alteração, ativação, desativação, soft delete e restauração de módulos;
- criação, alteração, desativação, soft delete, restauração e rotação de OAuth Clients dos módulos;
- emissões, falhas de autenticação e revogações relacionadas a Client Credentials;
- criação, alteração, desativação, soft delete e restauração de permissões;
- criação, alteração, desativação, soft delete e restauração de papéis;
- atribuição, encerramento e restauração de papéis;
- ativação e desativação do acesso de usuários a módulos;
- atribuição, encerramento e restauração de concessões e negações diretas;
- alteração de propriedade da instalação;
- redefinição administrativa de MFA;
- revogação administrativa de sessões;
- eventos relevantes de login e MFA.

Cada evento deve registrar, quando aplicável:

- ator;
- ação;
- alvo;
- organização;
- valores relevantes anteriores e posteriores, com proteção de segredos;
- data e hora;
- endereço IP;
- identificação da sessão;
- resultado.

Segredos, senhas, tokens completos e códigos TOTP não devem ser gravados na auditoria.

Os eventos de auditoria devem ser retidos por 3 anos contados da ocorrência e, ao final desse período, expurgados fisicamente por processo automatizado em até 30 dias, salvo configuração de retenção superior ou bloqueio de expurgo por obrigação legal, regulatória, contratual ou investigação em curso.

### RF-021 — Ciclo de vida, soft delete e expurgo

O sistema deve:

- aplicar soft delete, nunca exclusão física, às identidades, organizações, memberships, módulos, habilitações de módulos para organizações, OAuth Clients, autorizações de serviço, papéis, permissões, composições de papéis, atribuições de papéis e sobrescritas diretas;
- registrar data, ator e motivo do soft delete, quando informado;
- excluir registros logicamente removidos das consultas e decisões de autorização normais;
- oferecer restauração controlada quando não houver conflito de unicidade, segurança ou integridade;
- preservar identificadores estáveis e relacionamentos necessários à auditoria;
- impedir que interfaces, APIs, jobs ou comandos administrativos comuns executem hard delete dessas entidades;
- permitir expurgo físico somente para logs, eventos de auditoria e dados técnicos efêmeros, conforme a política de retenção;
- manter metadados históricos de OAuth Clients, mas eliminar material secreto revogado quando ele não for mais necessário para validação ou segurança;
- auditar soft deletes, restaurações, alterações de retenção, bloqueios de expurgo e expurgos executados.

### RF-022 — Painel administrativo

O painel deve permitir, conforme permissões:

- administrar identidades;
- administrar organizações;
- administrar módulos e seus OAuth Clients;
- administrar autorizações de serviço para Client Credentials;
- administrar permissões e papéis de cada módulo;
- ativar módulos para usuários por meio de um botão ou ação equivalente que exija a seleção de ao menos um papel;
- administrar atribuições de papéis, concessões diretas e negações diretas;
- visualizar permissões efetivas;
- configurar MFA da organização;
- consultar sessões;
- consultar auditoria;
- administrar as configurações públicas e a identidade visual da instalação.

Elementos de interface devem verificar permissões, mas a autorização definitiva deve ocorrer no servidor.

---

## 12. Regras de negócio

### RN-001

Uma identidade é global e não deve ser duplicada para cada organização.

### RN-002

Um usuário só pode atuar em uma organização à qual possua membership ativo.

### RN-003

Um módulo só pode ser acessado em uma organização na qual esteja habilitado.

### RN-004

O acesso de um usuário a um módulo exige membership ativo, organização e módulo ativos, módulo habilitado para a organização, ao menos um papel ativo e ao menos uma permissão efetiva na combinação entre usuário, organização e módulo.

### RN-005

A ação `Ativar módulo` exige a seleção de ao menos um papel e só pode ser concluída quando a configuração resultante conceder ao usuário ao menos uma permissão efetiva. Concessões e negações diretas podem ser informadas opcionalmente, mas não ativam nem mantêm o acesso sem um papel ativo.

### RN-006

Uma permissão só pode ser concedida ou negada diretamente, e um papel só pode ser atribuído, quando seu módulo estiver habilitado para a organização. Nenhuma concessão ou negação direta pode ser criada ou mantida sem ao menos um papel ativo no mesmo contexto.

### RN-007

Cada papel pertence a um módulo e agrupa somente permissões desse módulo. Papéis globais do módulo podem ser reutilizados entre organizações; papéis organizacionais ficam restritos à organização e ao módulo de origem. Aplicações integradas devem verificar permissões, não nomes de papéis.

### RN-008

Para cada combinação entre usuário, organização, módulo e permissão, pode existir no máximo uma sobrescrita direta: `CONCEDER` adiciona uma permissão não herdada e `NEGAR` retira uma permissão herdada dos papéis. Os dois efeitos não podem coexistir, e a alteração de um efeito para o outro substitui a sobrescrita anterior. Sobrescritas diretas devem ser utilizadas apenas como exceção.

### RN-009

O usuário não pode conceder acesso acima de seu limite delegável.

### RN-010

O proprietário da instalação não é determinado pelo valor de sua chave primária.

### RN-011

O proprietário não pode ser removido ou desativado sem transferência de propriedade.

### RN-012

O MFA do proprietário não pode ser desabilitado por uma organização.

### RN-013

A exigência de MFA organizacional é avaliada depois que a organização ativa for determinada.

### RN-014

Um usuário autenticado somente com senha deve realizar step-up authentication antes de entrar em um contexto que exija MFA.

### RN-015

ID Token comprova autenticação; Access Token autoriza acesso à API.

### RN-016

Um Access Token representa somente uma organização e uma audiência.

### RN-017

Módulos e aplicações integradas não podem receber a senha ou o segredo TOTP do usuário.

### RN-018

Somente administradores da instalação com permissões globais adequadas podem cadastrar módulos e OAuth Clients. Permissões podem ser criadas por administradores globais ou organizacionais autorizados e permanecem reutilizáveis dentro do módulo. Papéis podem ser globais ou restritos à organização, conforme o escopo de criação.

### RN-019

Entidades de domínio importantes e vínculos de autorização nunca podem ser excluídos fisicamente. Toda remoção funcional deve ser representada por desativação, soft delete ou término de vigência, conforme a natureza do registro.

### RN-020

Registros excluídos logicamente não participam de autenticação, autorização, emissão de tokens nem listagens operacionais padrão, mas permanecem disponíveis para auditoria e restauração controlada.

### RN-021

Logs de auditoria são retidos por 3 anos a partir da ocorrência. Após o término da retenção, devem ser expurgados fisicamente em até 30 dias, exceto quando houver retenção superior configurada ou bloqueio de expurgo aplicável.

### RN-022

Uma permissão pertence a exatamente um módulo e pode ser reutilizada em qualquer organização que possua esse módulo habilitado, inclusive quando criada por administrador organizacional. A permissão nunca pode ser utilizada em outro módulo.

### RN-023

Uma identidade pode possuir autorização para atribuir papéis e conceder ou negar permissões dentro de uma organização sem possuir autorização para criar novas permissões ou papéis.

### RN-024

No Client Credentials, o OAuth Client atua em nome próprio. O Access Token não representa usuário humano, não contém ID Token associado e é limitado a uma organização, uma audiência, um módulo e às permissões de serviço explicitamente autorizadas.

### RN-025

A última organização é uma preferência da identidade e não do módulo. O sistema sempre deve revalidá-la para o módulo ou OAuth Client de destino antes de reutilizá-la.

---

## 13. Fluxos principais

### 13.1 Instalação

1. Operador inicia instalação.
2. Sistema solicita os dados da primeira identidade.
3. Identidade é criada.
4. Identidade é registrada como proprietária.
5. Sistema exige configuração e confirmação do TOTP.
6. Códigos de recuperação são apresentados.
7. Sistema carrega `APP_NAME` como nome inicial e permite configurar a identidade visual da instalação.
8. Evento é auditado.

### 13.2 Primeiro acesso OIDC a um módulo com múltiplas organizações

1. Usuário acessa uma aplicação integrada de um módulo.
2. O OAuth Client da aplicação inicia Authorization Code Flow com PKCE.
3. Identity Platform valida o OAuth Client e identifica o módulo correspondente.
4. Usuário informa e-mail e senha.
5. Sistema identifica várias organizações válidas.
6. Usuário seleciona uma organização.
7. Sistema consulta a política de MFA.
8. Se necessário, usuário configura ou informa TOTP.
9. Sistema valida se o módulo está habilitado para a organização e se o usuário possui ao menos um papel ativo e ao menos uma permissão efetiva naquele módulo e contexto.
10. Sistema emite Authorization Code.
11. A aplicação integrada troca o código por ID Token e Access Token.
12. Organização escolhida é registrada como a última organização válida da identidade.

### 13.3 Acesso posterior

1. A aplicação integrada redireciona o usuário ao Identity Platform.
2. Sessão central é reconhecida, quando ainda válida.
3. Última organização da identidade é recuperada.
4. Membership, organização, módulo, existência de ao menos um papel ativo, existência de ao menos uma permissão efetiva e política de MFA são reavaliados.
5. Se a organização anterior não for válida para o módulo solicitado, o usuário escolhe outra organização aplicável.
6. Se não houver necessidade de nova interação, o Authorization Code é emitido.
7. A aplicação integrada troca o código por tokens.

### 13.4 Usuário com apenas uma organização

1. Sistema identifica somente uma organização válida.
2. Organização é selecionada automaticamente.
3. Sistema lista os módulos acessíveis naquela organização.
4. Se houver apenas um módulo, o login desse módulo é iniciado automaticamente.
5. Se houver vários módulos e nenhum destino tiver sido informado por OAuth Client, o usuário escolhe o módulo.
6. Nenhuma seleção redundante é apresentada quando organização ou módulo já estiverem determinados.

### 13.5 Mudança para organização que exige MFA

1. Usuário está autenticado apenas por senha.
2. Usuário solicita acesso a uma organização que exige MFA.
3. Sistema interrompe a emissão do código.
4. Sistema solicita TOTP ou configuração inicial.
5. Após validação, autenticação passa a registrar senha e OTP.
6. Fluxo OIDC continua.

### 13.6 Ativação de módulo para usuário

1. Administrador acessa um usuário dentro de uma organização.
2. Administrador seleciona um módulo habilitado para a organização.
3. Administrador aciona o botão `Ativar módulo`.
4. Sistema apresenta apenas papéis e permissões daquele módulo disponíveis no contexto.
5. Administrador seleciona obrigatoriamente ao menos um papel.
6. Administrador pode, opcionalmente, selecionar permissões granulares para `CONCEDER` ou `NEGAR`.
7. Sistema valida membership, organização, módulo, papel, sobrescritas, escopo administrável e limite delegável.
8. Sistema calcula as permissões efetivas resultantes e impede a ativação se não houver ao menos uma.
9. Papel e eventuais sobrescritas são registrados atomicamente.
10. Acesso ao módulo é ativado e o evento é auditado.

### 13.7 Sobrescrita direta de permissão

1. Administrador acessa as permissões do usuário.
2. Sistema diferencia permissões herdadas, concedidas diretamente e negadas diretamente.
3. Sistema confirma que o usuário possui ao menos um papel ativo naquele módulo e organização.
4. Administrador escolhe uma permissão disponível.
5. Para uma permissão não herdada, o administrador pode escolher `CONCEDER`.
6. Para uma permissão herdada de papel, o administrador pode escolher `NEGAR`.
7. Se já existir uma sobrescrita para a mesma permissão, a nova escolha a substitui; o sistema nunca mantém concessão e negação simultâneas.
8. Sistema valida organização, módulo, papel ativo, escopo administrável e limite delegável.
9. Sobrescrita direta é registrada, as permissões efetivas são recalculadas, o acesso ao módulo é reavaliado e o evento é auditado.

### 13.8 Autenticação máquina a máquina

1. Serviço envia ao Token Endpoint `grant_type=client_credentials`, suas credenciais, a audiência e o contexto organizacional desejado.
2. Identity Platform autentica o OAuth Client confidencial.
3. Sistema valida client, módulo, organização, habilitação do módulo e permissões de serviço autorizadas.
4. Se o client estiver autorizado para apenas uma organização, ela pode ser determinada automaticamente; caso contrário, a organização deve ser informada e validada.
5. Sistema emite um Access Token de vida curta cujo sujeito representa o OAuth Client.
6. O token não contém identidade humana nem produz ID Token ou Refresh Token.
7. O serviço utiliza o Access Token na API da audiência autorizada.
8. A emissão e eventuais falhas são auditadas.

---

## 14. Histórias de usuário prioritárias

### HU-001 — Criar organização

Como administrador global, quero criar uma organização para que seus usuários e acessos possam ser administrados isoladamente.

**Critérios de aceitação:**

- exige permissão adequada;
- identificador é único;
- organização inicia em estado definido;
- operação é auditada.

### HU-002 — Adicionar usuário a uma organização

Como administrador autorizado, quero adicionar uma identidade a uma organização para que ela possa receber acessos.

**Critérios de aceitação:**

- identidade existente é reutilizada;
- não são criados usuários duplicados pelo mesmo e-mail;
- quando uma nova identidade for criada, o administrador define uma senha temporária;
- a senha temporária deve ser alterada no primeiro login e não pode ser consultada novamente pelo administrador;
- vínculo é criado somente em organização administrável;
- operação é auditada.

### HU-003 — Cadastrar módulo

Como administrador da instalação, quero cadastrar um módulo que represente um sistema da empresa para centralizar seus acessos.

**Critérios de aceitação:**

- módulo possui identificador estável, nome e descrição;
- módulo pode possuir um ou mais OAuth Clients;
- módulo permite cadastrar seu próprio catálogo de permissões e papéis;
- módulo pode ser habilitado somente para organizações selecionadas;
- operação é auditada.

### HU-004 — Criar papel

Como administrador autorizado, quero criar um papel dentro de um módulo para disponibilizar um perfil de acesso adequado ao meu escopo.

**Critérios de aceitação:**

- o papel pertence a exatamente um módulo;
- somente permissões válidas do mesmo módulo podem ser incluídas;
- papel criado em escopo global pode ser reutilizado entre organizações;
- papel criado por administrador organizacional fica restrito à organização e ao módulo de origem;
- papel possui identificador estável e escopo definido;
- alterações afetam as permissões efetivas dos usuários vinculados;
- operação é auditada.

### HU-005 — Ativar módulo para usuário

Como administrador autorizado, quero ativar um módulo para um usuário selecionando obrigatoriamente um papel e, opcionalmente, concessões ou negações granulares.

**Critérios de aceitação:**

- a ação está disponível por meio do botão `Ativar módulo` na gestão do usuário dentro de uma organização;
- somente módulos habilitados para a organização podem ser ativados;
- a ativação não pode ser concluída sem a seleção de ao menos um papel válido do módulo;
- concessões e negações diretas são opcionais;
- a configuração resultante deve conceder ao usuário ao menos uma permissão efetiva;
- papel e sobrescritas opcionais são validados e registrados atomicamente;
- o acesso passa a ser reconhecido pela existência simultânea de ao menos um papel ativo e ao menos uma permissão efetiva no contexto usuário, organização e módulo;
- concessões diretas isoladas não ativam o módulo;
- operação é auditada.

### HU-006 — Conceder ou negar permissão diretamente

Como administrador autorizado, quero conceder ou negar uma permissão individual como exceção sem criar ou alterar um papel.

**Critérios de aceitação:**

- concessão ou negação direta é identificada como uma sobrescrita excepcional;
- usuário possui ao menos um papel ativo no mesmo módulo e organização;
- não ultrapassa o limite delegável;
- para a mesma permissão e contexto, existe no máximo uma sobrescrita direta, com efeito `CONCEDER` ou `NEGAR`;
- `CONCEDER` adiciona uma permissão não herdada e `NEGAR` retira uma permissão herdada de papel;
- mudar o efeito substitui a sobrescrita anterior sem criar conflito;
- permissões efetivas são recalculadas após a alteração;
- o acesso ao módulo é desativado se o recálculo resultar em zero permissões efetivas;
- pode ser removida;
- operação é auditada.

### HU-007 — Configurar MFA da organização

Como administrador autorizado da organização, quero definir se MFA é obrigatório para seus usuários.

**Critérios de aceitação:**

- exige permissão de segurança organizacional;
- não altera a exigência do proprietário;
- usuários sem TOTP são conduzidos à configuração no próximo acesso aplicável;
- mudança é auditada.

### HU-008 — Entrar em um módulo

Como usuário, quero entrar em um dos sistemas da empresa usando minha identidade central para não manter credenciais separadas.

**Critérios de aceitação:**

- módulo e aplicação integrada não recebem a senha;
- organização é determinada conforme as regras;
- acesso exige ao menos um papel ativo e ao menos uma permissão efetiva do módulo na organização selecionada;
- MFA é aplicado quando necessário;
- aplicação integrada recebe ID Token válido;
- API recebe Access Token limitado ao módulo e à sua audiência.

### HU-009 — Trocar de organização

Como usuário com acesso a várias organizações, quero trocar o contexto para utilizar o módulo com as permissões da organização escolhida.

**Critérios de aceitação:**

- somente organizações válidas são apresentadas;
- novo contexto gera tokens específicos;
- permissões de outra organização não são carregadas;
- MFA adicional é solicitado quando necessário.

### HU-010 — Revogar sessão

Como usuário, quero visualizar e encerrar minhas sessões para controlar dispositivos que acessam minha conta.

**Critérios de aceitação:**

- usuário visualiza somente suas sessões;
- encerramento invalida o acesso conforme a estratégia definida;
- operação é auditada.

### HU-011 — Proteger o próprio Identity Platform

Como proprietário, quero controlar quem pode administrar usuários, módulos, OAuth Clients, papéis e permissões para que o painel não dependa de acessos fixos no código.

**Critérios de aceitação:**

- endpoints administrativos exigem permissões;
- elementos da interface respeitam permissões;
- servidor sempre revalida autorização;
- operações sensíveis são auditadas.

### HU-012 — Criar permissão no módulo

Como administrador global ou organizacional autorizado, quero cadastrar uma permissão atômica para que o módulo consumidor possa utilizá-la em suas regras de autorização.

**Critérios de aceitação:**

- a permissão pertence a exatamente um módulo;
- administrador organizacional só pode criá-la a partir de uma organização em que o módulo esteja habilitado;
- a permissão pode ser reutilizada em outras organizações que utilizem o mesmo módulo;
- a permissão não pode ser utilizada em outro módulo;
- criar o identificador não implica que a respectiva regra já esteja implementada no módulo consumidor;
- a operação é auditada.

### HU-013 — Autenticar serviço

Como serviço integrado, quero obter um Access Token por Client Credentials para acessar uma API sem depender de um usuário humano.

**Critérios de aceitação:**

- somente OAuth Client confidencial e autorizado pode utilizar o grant;
- o token representa o client;
- organização, módulo, audiência e permissões de serviço são validados;
- não são emitidos ID Token nem Refresh Token;
- o token possui validade curta;
- a emissão é auditada.

### HU-014 — Personalizar instalação

Como proprietário, quero definir o nome e a identidade visual da instalação para que o sistema clonado represente o produto que será desenvolvido.

**Critérios de aceitação:**

- `APP_NAME` fornece o valor inicial e o fallback;
- nome, nomes curto e institucional, logotipos, favicon, cores, idioma, fuso horário e dados públicos de suporte podem ser alterados;
- secrets e configurações de infraestrutura não são armazenados nesse cadastro;
- alterações são auditadas.

### HU-015 — Executar aplicação de referência

Como desenvolvedor, quero uma aplicação Laravel de referência para validar login OIDC, tokens e permissões antes de integrar meus próprios módulos.

**Critérios de aceitação:**

- aplicação é entregue separadamente e vinculada a um módulo de exemplo;
- demonstra Authorization Code Flow com PKCE;
- valida ID Token;
- envia Access Token para uma API protegida;
- demonstra proteção de uma operação por permissão;
- possui documentação de execução e integração.

---

## 15. Requisitos de segurança do produto

1. HTTPS deve ser obrigatório fora de ambiente local.
2. Authorization Code Flow deve utilizar PKCE.
3. Redirect URIs devem ser validadas por correspondência exata.
4. `state` deve ser validado.
5. `nonce` deve ser validado em fluxos OIDC.
6. Tokens devem possuir emissor, audiência e expiração verificáveis.
7. Chaves de assinatura devem suportar rotação.
8. Access Tokens devem possuir vida curta.
9. Refresh Tokens, quando usados, devem possuir proteção e rotação.
10. Senhas, tokens completos e segredos não devem aparecer em logs.
11. Segredos de OAuth Clients devem ser armazenados de forma segura.
12. TOTP e códigos de recuperação devem possuir proteção adequada.
13. Operações sensíveis devem exigir reautenticação quando aplicável.
14. O sistema deve impedir autorização entre organizações.
15. Alterações de permissões devem ter efeito previsível sobre sessões e tokens.
16. Recuperações administrativas devem ser restritas e auditadas.
17. A interface não deve ser a única camada de autorização.
18. O produto deve seguir práticas de minimização de dados.
19. Senhas temporárias devem ser armazenadas somente como hash, possuir expiração e exigir alteração no primeiro login.
20. OAuth Clients confidenciais devem proteger seus secrets, suportar rotação e utilizar TLS no Token Endpoint.
21. Access Tokens emitidos por Client Credentials devem identificar o client como sujeito e não podem conter claims que indiquem autenticação humana.
22. A ausência de consentimento interativo para clients confiáveis não pode dispensar validações de Redirect URI, scope, PKCE, organização, módulo, audiência e permissões.

---

## 16. Requisitos de usabilidade

- O usuário deve compreender em qual organização está atuando.
- A organização ativa deve ser visível no painel.
- Usuários com uma única organização não devem enfrentar uma seleção desnecessária.
- Permissões herdadas, concedidas diretamente e negadas diretamente devem ser visualmente distinguíveis.
- A origem de uma permissão efetiva deve poder ser consultada.
- Desativações, soft deletes e restaurações devem exigir confirmação e informar seu efeito sobre acessos.
- Segredos devem ser exibidos apenas quando necessário.
- A configuração de TOTP deve orientar QR Code, confirmação e códigos de recuperação.
- Mensagens de autorização não devem revelar permissões sensíveis desnecessariamente.
- O painel deve ser responsivo e acessível.

---

## 17. Requisitos de auditoria e privacidade

- Auditoria deve ser consultável por escopo.
- Administradores organizacionais não podem consultar eventos de outras organizações.
- Eventos globais exigem permissão global.
- Dados pessoais exibidos devem ser limitados ao necessário.
- Eventos de auditoria devem ter retenção padrão de 3 anos e expurgo físico automatizado em até 30 dias após o vencimento.
- A instalação pode configurar retenção superior à padrão para atender obrigações legais, regulatórias ou contratuais.
- Um evento sujeito a investigação, disputa ou obrigação de preservação deve aceitar bloqueio de expurgo (`legal hold`) até sua liberação formal.
- Logs operacionais sem finalidade de auditoria devem ter retenção padrão de 90 dias e não podem conter senhas, tokens completos, secrets ou códigos TOTP.
- Códigos de autorização, nonces, tokens de redefinição, convites e outros artefatos efêmeros devem ser expurgados após sua expiração, respeitando o menor prazo tecnicamente seguro.
- Dados já vencidos podem permanecer em backups imutáveis somente até o ciclo normal de rotação, limitado a 90 dias, sem reintrodução no ambiente ativo fora de um procedimento controlado que reaplique as regras de retenção.
- Solicitações válidas de eliminação de dados pessoais devem ser atendidas, quando aplicáveis, por anonimização ou pseudonimização irreversível dos atributos pessoais, preservando apenas o registro mínimo necessário à integridade e à auditoria.
- A implantação deve poder atender obrigações de privacidade aplicáveis, incluindo princípios da LGPD, sem alegar conformidade automática apenas pela instalação.

---

## 18. Métricas de aceitação do MVP

As métricas iniciais são técnicas e operacionais, não comerciais:

1. Nenhum teste de isolamento permite acesso cruzado entre organizações.
2. Cem por cento das operações administrativas classificadas como sensíveis geram auditoria.
3. Cem por cento dos endpoints administrativos possuem autorização no servidor.
4. Uma aplicação de referência vinculada a um módulo consegue integrar login utilizando discovery OIDC.
5. ID Tokens são rejeitados quando emissor, audiência, assinatura, expiração ou nonce forem inválidos.
6. Access Tokens são rejeitados por audiência incorreta.
7. Tokens de uma organização não autorizam operações em outra.
8. Usuários de organização com MFA obrigatório não recebem autorização sem completar o desafio.
9. Usuários de organização sem MFA obrigatório conseguem autenticar somente com senha, salvo exigência global.
10. Proprietário da instalação não consegue desabilitar seu próprio MFA sem procedimento controlado.
11. Alterações de papéis e sobrescritas diretas produzem permissões efetivas previsíveis, sem coexistência de concessão e negação diretas para a mesma permissão e contexto.
12. O acesso a um módulo somente é autorizado quando o usuário possui simultaneamente ao menos um papel ativo e ao menos uma permissão efetiva naquele módulo e organização; concessões diretas isoladas não autorizam a entrada.
13. O projeto fornece documentação suficiente para cadastrar um módulo e integrar ao menos uma aplicação Laravel de exemplo.
14. Nenhuma operação funcional ou administrativa comum executa hard delete de entidades de domínio ou vínculos de autorização.
15. Registros excluídos logicamente deixam de produzir efeitos de autenticação e autorização, preservam seu histórico e podem ser restaurados de forma controlada.
16. Eventos de auditoria vencidos são expurgados conforme a retenção padrão de 3 anos, respeitando configurações superiores e bloqueios de expurgo.
17. Senhas temporárias exigem alteração no primeiro login, expiram e não podem ser recuperadas em texto claro.
18. Administradores organizacionais autorizados conseguem criar permissões reutilizáveis no mesmo módulo e papéis restritos à sua organização e módulo.
19. Uma identidade com autorização apenas para atribuir acessos não consegue criar permissões ou papéis.
20. Client Credentials emite Access Token de serviço válido sem ID Token, identidade humana ou permissões além das autorizadas para o client.
21. A última organização é armazenada por identidade, revalidada para o destino e não particionada por módulo.
22. A instalação pode alterar nome e identidade visual sem modificar o nome técnico ou o código do skeleton.

---

## 19. Restrições conhecidas

- O projeto será open source.
- O projeto será distribuído sob a licença MIT.
- O backend será construído em Laravel.
- O frontend administrativo deverá utilizar Vue.js quando necessário, com preferência atual por Vue 3, TypeScript e Inertia.
- O projeto deverá utilizar `samuel-nunes/laravel-ddd-toolkit`.
- A arquitetura deverá ser modular, vertical e hexagonal por padrão.
- Repositories e ports de persistência não deverão ser criados apenas por cerimônia; serão utilizados onde houver fronteira ou complexidade real.
- O projeto deverá gerar documentação que auxilie humanos e agentes de IA a compreender cada módulo arquitetural do código.

Detalhes de framework, pacotes, banco, cache, filas, servidor OAuth/OIDC e deployment pertencem ao TRD e aos ADRs.

---

## 20. Riscos de produto

### 20.1 Escalada de privilégio

Uma implementação ingênua da permissão “atribuir permissões” pode permitir que um administrador conceda poderes superiores aos próprios.

**Mitigação esperada:** limite delegável, escopo e auditoria.

### 20.2 Tokens excessivamente grandes

Incluir permissões de todas as organizações em um único token pode causar vazamento contextual e problemas operacionais.

**Mitigação esperada:** um Access Token por audiência e organização.

### 20.3 Permissões arbitrárias

Permitir que organizações inventem permissões sem correspondência no módulo pode criar uma falsa sensação de proteção.

**Mitigação esperada:** catálogos de permissões e papéis governados pela instalação e vinculados a módulos.

### 20.4 Revogação tardia

JWTs já emitidos podem continuar válidos depois de uma mudança de acesso.

**Mitigação esperada:** validade curta, versionamento de autorização e/ou revogação conforme decisão técnica.

### 20.5 Complexidade protocolar

OAuth/OIDC implementados parcialmente podem introduzir vulnerabilidades.

**Mitigação esperada:** uso de componentes maduros, testes de conformidade e ADR específico.

### 20.6 Recuperação do proprietário

Perda do TOTP e dos códigos de recuperação pode bloquear a instalação.

**Mitigação esperada:** procedimento administrativo restrito, explícito e auditado.

---

## 21. Decisões de validação

Esta seção consolida as decisões tomadas durante a validação do PRD.

### Q-001 — Nome do produto

**Decisão registrada:** o projeto-base se chamará **Laravel DDD Skeleton with Identity Platform**.

O nome técnico identifica um skeleton clonável para iniciar novos sistemas Laravel com arquitetura DDD e Identity Platform embutida. O nome exibido por cada instalação será independente, inicializado por `APP_NAME` e posteriormente personalizável pelas configurações persistidas da instalação.

### Q-002 — Criação de usuários

**Decisão registrada:** o administrador define uma senha temporária ao cadastrar uma nova identidade.

A senha temporária deve ser alterada obrigatoriamente no primeiro login, não pode ser consultada novamente pelo administrador e deve poder ser invalidada por expiração ou redefinição administrativa.

### Q-003 — Criação de papéis

**Decisão registrada:** administradores globais autorizados podem criar papéis globais dentro de cada módulo. Esses papéis podem ser reutilizados entre organizações.

Administradores organizacionais autorizados também podem criar papéis, mas o papel criado fica restrito à combinação entre organização e módulo de origem e não pode ser reutilizado em outra organização ou módulo.

### Q-004 — Reutilização de papéis entre organizações

**Decisão registrada:** um papel global cadastrado no catálogo central pertence a um módulo e pode ser reutilizado por todas as organizações nas quais esse módulo esteja habilitado. A atribuição continua isolada por organização.

Papéis criados por administradores organizacionais são exceção à reutilização e permanecem restritos à organização e ao módulo de origem.

### Q-005 — Criação de permissões atômicas

**Decisão registrada:** administradores globais e administradores organizacionais com autorização específica podem cadastrar permissões atômicas.

Toda permissão pertence a exatamente um módulo. Mesmo quando criada por administrador organizacional, ela pode ser reutilizada em outras organizações que possuam aquele módulo habilitado, mas nunca em outro módulo.

Criar uma permissão apenas registra seu identificador na plataforma. Cabe ao módulo consumidor implementar a validação e a regra correspondente; uma permissão desconhecida pelo módulo não produz proteção ou capacidade automaticamente.

Como alterações posteriores podem afetar várias organizações, mudar o identificador, desativar, excluir logicamente ou restaurar uma permissão reutilizável exige autorização global. O administrador organizacional autorizado pode criá-la e utilizá-la, mas não provocar alterações transversais sem a permissão global correspondente.

As autorizações para criar e atribuir são independentes. Uma identidade pode atribuir papéis, conceder permissões e negar permissões somente dentro de determinada organização sem possuir autorização para criar novas permissões ou papéis.

### Q-006 — Acesso a módulo

**Decisão registrada:** o acesso do usuário a um módulo é validado pela existência simultânea de ao menos um papel ativo e ao menos uma permissão efetiva na combinação entre usuário, organização e módulo, além de membership, organização, módulo e habilitação organizacional válidos.

O painel deve oferecer a ação `Ativar módulo`. Para concluí-la, o administrador deve selecionar obrigatoriamente ao menos um papel do módulo e pode selecionar, opcionalmente, permissões granulares para conceder ou negar. A configuração resultante deve conceder ao usuário ao menos uma permissão efetiva.

Concessões e negações diretas alteram as permissões efetivas, mas não ativam nem mantêm o acesso ao módulo sem um papel ativo. Se as negações ou qualquer alteração em papéis e permissões removerem todas as permissões efetivas, o acesso ao módulo é desativado, ainda que permaneça um papel ativo atribuído. O acesso volta a ser reconhecido quando existir novamente ao menos um papel ativo e ao menos uma permissão efetiva, desde que as demais condições permaneçam válidas.

Também não é permitido criar concessões ou negações diretas para uma identidade que não possua ao menos um papel ativo no mesmo módulo e organização.

### Q-007 — Política de MFA

**Decisão registrada:** a configuração organizacional possui somente os estados:

- `OBRIGATÓRIO`;
- `OPCIONAL`.

A organização define se exigirá MFA naquele contexto, mas não impede que uma identidade utilize MFA por segurança própria ou por exigência de outra organização.

### Q-008 — Última organização

**Decisão registrada:** a última organização será armazenada centralmente por identidade, sem separação por módulo.

Depois de determinar a organização, o sistema apresenta os módulos acessíveis. Se houver somente um, o login é iniciado automaticamente; se houver vários, o usuário escolhe. Quando a autenticação for iniciada diretamente por um OAuth Client, seu módulo já será o destino e não haverá seleção redundante.

A organização recuperada deve sempre ser revalidada para o módulo ou OAuth Client atual.

### Q-009 — Consentimento

**Decisão registrada:** os OAuth Clients do MVP serão aplicações internas confiáveis e não haverá tela interativa de consentimento.

A ausência da tela não elimina a validação de client, Redirect URI, scope, PKCE, organização, módulo e permissões, nem o registro da autorização. Suporte a aplicações externas e consentimento explícito poderá ser adicionado posteriormente.

### Q-010 — Exclusão de dados

**Decisão registrada:** identidades, organizações, memberships, módulos, habilitações de módulos, OAuth Clients, autorizações de serviço, papéis, permissões, composições de papéis, atribuições e sobrescritas diretas não podem sofrer exclusão física. A suspensão temporária usa desativação; a remoção funcional usa soft delete ou término de vigência, sempre com preservação de auditoria.

O hard delete fica restrito a logs e dados técnicos efêmeros. Eventos de auditoria possuem retenção padrão de 3 anos e são expurgados fisicamente em até 30 dias após o vencimento, salvo retenção superior configurada ou `legal hold`. Logs operacionais possuem retenção padrão de 90 dias. Artefatos efêmeros de autenticação são expurgados após sua expiração.

### Q-011 — Client Credentials

**Decisão registrada:** OAuth 2.0 Client Credentials faz parte do MVP.

O fluxo será permitido apenas para OAuth Clients confidenciais autorizados. O Access Token representa o próprio client e fica limitado a uma organização, uma audiência, um módulo e às permissões de serviço configuradas. O fluxo não emite ID Token nem representa usuário humano.

### Q-012 — Aplicação de referência

**Decisão registrada:** o projeto entregará uma aplicação de exemplo separada, vinculada a um módulo, que demonstre:

- login OIDC;
- validação de ID Token;
- envio de Access Token;
- proteção por permissões.

---

## 22. Critério de aprovação deste PRD

O PRD será considerado aprovado quando:

1. objetivos e não objetivos estiverem aceitos;
2. atores estiverem corretos;
3. fluxos de autenticação, organização e MFA estiverem validados;
4. modelo de papéis, permissões e delegação estiver aceito;
5. decisões da seção 21 estiverem refletidas de forma consistente em todo o documento;
6. não houver ambiguidades relevantes sobre o comportamento esperado do MVP.

Após aprovação, o próximo artefato será o TRD, seguido pelos ADRs necessários e pelo plano detalhado de implementação para o Codex.
