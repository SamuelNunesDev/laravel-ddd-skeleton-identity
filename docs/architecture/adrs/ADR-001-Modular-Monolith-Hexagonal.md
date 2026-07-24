# ADR-001 — Monólito modular vertical com arquitetura hexagonal

**Status:** Aceito  
**Data:** 2026-07-24  
**Decisores:** mantenedores do projeto

## Contexto

O produto deve ser clonado como ponto de partida para sistemas Laravel que já necessitam autenticação, identidade, organizações, OAuth/OIDC, autorização e auditoria. Ele precisa favorecer evolução modular sem introduzir a complexidade operacional de serviços distribuídos no MVP.

O projeto também deve adotar o `samuel-nunes/laravel-ddd-toolkit` e manter uma organização previsível para pessoas e agentes de programação.

## Decisão

Adotar um monólito modular organizado por capacidades de negócio. Cada módulo será vertical e autônomo, com arquitetura hexagonal como padrão:

```text
app/Modules/<Module>/
├── Domain/
├── Application/
│   └── Ports/
│       ├── In/
│       └── Out/
└── Infrastructure/
    ├── Http/Controllers/
    └── Persistence/Adapters/
```

Regras:

- o domínio não dependerá de Laravel, Eloquent, HTTP ou infraestrutura;
- casos de uso serão expostos por Ports In;
- dependências externas serão definidas em `Application/Ports/Out`;
- adapters concretos ficarão em `Infrastructure`;
- controllers ficarão em `Infrastructure/Http/Controllers`;
- Eloquent models, queries e adapters de persistência não serão colocados no domínio;
- repositórios não serão uma abstração obrigatória;
- quando uma abstração de persistência for necessária, serão preferidos `make:port` e `make:adapter`;
- `make:repository` será uma opção explícita, não o padrão;
- o núcleo `Shared` será mínimo e não poderá virar um módulo de utilidades sem dono;
- módulos conversarão por contratos de aplicação e eventos internos, sem acesso direto às tabelas de outro módulo;
- `ddd:check` será executado no CI para validar limites arquiteturais.

## Justificativa

Essa estrutura equilibra isolamento, testabilidade e velocidade de entrega. O monólito mantém transações e operação simples, enquanto os limites modulares reduzem acoplamento e preservam uma rota de extração futura caso algum módulo precise se tornar serviço independente.

## Consequências

### Positivas

- implantação única no MVP;
- transações locais simples;
- estrutura previsível para novos módulos;
- domínio testável sem framework;
- menor risco de acoplamento acidental;
- possibilidade de extração gradual de módulos.

### Negativas

- exige disciplina e testes arquiteturais;
- eventos internos não garantem isolamento operacional como um broker externo;
- uma implantação ainda escala o conjunto da aplicação;
- algumas funcionalidades simples terão mais arquivos que uma organização Laravel convencional.

## Alternativas consideradas

- **Laravel em camadas horizontais:** mais simples inicialmente, mas favorece acoplamento entre capacidades.
- **Microsserviços desde o início:** rejeitado pela complexidade operacional, consistência distribuída e custo desproporcional ao MVP.
- **Repository pattern obrigatório:** rejeitado porque cria abstrações sem benefício uniforme e pode apenas espelhar o ORM.

## Notas de implementação

- iniciar módulos com `make:module`;
- usar testes de dependência para impedir referências proibidas;
- documentar o contrato público de cada módulo;
- usar transações na camada de aplicação;
- eventos com efeitos externos devem usar outbox transacional quando confiabilidade for necessária.
