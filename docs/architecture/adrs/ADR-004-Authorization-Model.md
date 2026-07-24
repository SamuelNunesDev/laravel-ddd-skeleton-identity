# ADR-004 — Modelo de autorização por módulo, organização e delegação

**Status:** Aceito  
**Data:** 2026-07-24  
**Decisores:** mantenedores do projeto

## Contexto

Os sistemas construídos sobre o skeleton definirão permissões próprias. A plataforma de identidade administra catálogos, papéis, vínculos e efeitos, mas o módulo consumidor é responsável por aplicar suas regras.

Há diferenças importantes de escopo:

- uma permissão pertence a um módulo e pode ser reutilizada em várias organizações;
- um papel pode ser global ao módulo ou restrito a uma organização/módulo;
- uma identidade pode atribuir papéis e conceder ou negar permissões somente dentro de uma organização;
- atribuir, conceder e negar são poderes distintos de criar permissões;
- não é permitido conceder ou negar permissão diretamente sem que a identidade possua algum papel no mesmo contexto.

## Decisão

Adotar um modelo RBAC com efeitos diretos complementares:

```text
permissões efetivas = (permissões herdadas dos papéis ∪ concessões diretas) − negações diretas
```

Regras:

1. Permissões usam chave estável e são únicas por módulo.
2. Permissão criada por administrador organizacional torna-se disponível ao mesmo módulo em outras organizações.
3. Papel global pertence ao módulo e pode ser reutilizado em várias organizações.
4. Papel organizacional pertence exatamente a uma combinação organização/módulo.
5. Uma atribuição de papel liga identidade, organização, módulo e papel compatível.
6. Concessão ou negação direta exige ao menos um papel ativo da identidade naquela organização/módulo.
7. Para a mesma identidade, organização, módulo e permissão, concessão e negação são mutuamente exclusivas.
8. Negação explícita prevalece sobre concessões diretas e heranças.
9. Ações administrativas terão permissões independentes, incluindo:
   - criar permissões;
   - criar papéis;
   - atribuir e remover papéis;
   - conceder e remover concessões diretas;
   - negar e remover negações diretas.
10. Toda alteração será auditada com ator, escopo, estado anterior, estado posterior e justificativa quando exigida.
11. Credenciais de serviço terão autorização própria por scopes e políticas de cliente, sem simular papéis humanos.

## Justificativa

O modelo preserva a simplicidade conceitual de papéis, permite exceções explícitas e atende à delegação organizacional sem entregar poderes de administração de catálogo. A precedência da negação facilita bloqueios emergenciais e interpretação determinística.

## Consequências

### Positivas

- responsabilidades administrativas separadas;
- permissões reutilizáveis sem duplicação entre organizações;
- exceções locais possíveis;
- avaliação determinística;
- trilha completa de alterações.

### Negativas

- interface administrativa precisa explicar herança, concessão e negação;
- alterações em permissões globais podem afetar várias organizações;
- cache de autorização exige invalidação cuidadosa;
- papel mínimo pode ser criado apenas para habilitar efeitos diretos.

## Alternativas consideradas

- **RBAC puro:** não atende exceções diretas e negações.
- **ABAC completo:** flexível, porém complexo demais para o MVP.
- **Catálogo de permissões separado por organização:** rejeitado porque a mesma capacidade funcional, como `sales.orders.approve`, teria de ser cadastrada novamente em cada organização. Os códigos e identificadores poderiam divergir, e o módulo consumidor teria de mapear várias permissões equivalentes. A decisão vigente mantém a definição da permissão no módulo e restringe por organização somente sua atribuição e seus efeitos.
- **Concessão direta sem papel:** rejeitada por contrariar a decisão de governança do produto.

## Notas de implementação

- manter um `AuthorizationResolver` puro e amplamente testado;
- incrementar `authz_version` da identidade no escopo afetado após mudanças;
- invalidar cache por identidade/organização/módulo;
- impedir referências entre papéis e permissões de módulos incompatíveis;
- exibir na interface a origem de cada permissão efetiva;
- exigir confirmação reforçada para mudanças em permissões compartilhadas entre organizações.
