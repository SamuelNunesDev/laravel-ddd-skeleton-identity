# ADR-006 — Vue e Inertia para a interface administrativa

**Status:** Aceito  
**Data:** 2026-07-24  
**Decisores:** mantenedores do projeto

## Contexto

O skeleton precisa de uma interface administrativa para instalação, identidades, organizações, módulos, clientes OAuth, permissões, papéis, auditoria, sessões e personalização visual. O frontend deve permanecer integrado ao monólito Laravel e, ao mesmo tempo, oferecer componentes reutilizáveis e boa experiência de uso.

## Decisão

Usar:

- Vue 3 com Composition API;
- TypeScript;
- Inertia 3;
- Tailwind CSS 4;
- shadcn-vue como base de componentes;
- Vite para build;
- Playwright para fluxos críticos de ponta a ponta.

Laravel continuará responsável por rotas, casos de uso, autorização e composição inicial das páginas. A interface não será uma barreira de segurança: botões ocultos e guards do frontend apenas refletirão decisões já aplicadas no servidor.

Dados de personalização da instalação incluirão:

- nome público do sistema;
- nome curto;
- logotipo e favicon;
- cor principal e secundária;
- idioma e fuso horário padrão;
- dados de suporte;
- URLs institucionais e de política de privacidade;
- remetente padrão de e-mail.

Configurações sensíveis não serão armazenadas nessa tabela nem enviadas como props ao navegador.

## Justificativa

Inertia preserva o modelo de desenvolvimento Laravel, evita uma API duplicada apenas para a administração e oferece uma interface rica com Vue. TypeScript e um sistema de componentes reduzem inconsistência em telas de autorização, que possuem estados e consequências complexas.

## Consequências

### Positivas

- uma única aplicação implantável;
- navegação rica sem manter dois backends;
- validação e autorização centralizadas;
- componentes e layouts reutilizáveis;
- tipagem no frontend.

### Negativas

- frontend e backend continuam acoplados no ciclo de implantação;
- não produz automaticamente uma API pública completa;
- equipe precisa dominar Vue e Inertia;
- props excessivas podem aumentar payload e exposição de dados.

## Alternativas consideradas

- **Blade puro:** menor dependência, mas menos adequado às telas administrativas interativas previstas.
- **Livewire:** integração forte com Laravel, porém Vue foi escolhido como base extensível para sistemas clonados.
- **SPA desacoplada:** flexível, mas duplica contratos, autenticação e implantação sem benefício suficiente no MVP.

## Notas de implementação

- compartilhar somente props necessárias;
- usar componentes de confirmação para ações de alto impacto;
- mostrar origem e escopo de permissões;
- garantir navegação por teclado, contraste e mensagens acessíveis;
- aplicar Content Security Policy;
- manter tokens OAuth fora de armazenamento persistente do navegador quando o fluxo não exigir isso.
