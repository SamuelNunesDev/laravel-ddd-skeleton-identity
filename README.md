# Laravel DDD Skeleton with Identity Platform

Cloneable Laravel 13 skeleton for modular systems, with the architecture and delivery plan for an embedded Identity Platform.

M0 provides the executable repository foundation only. Identity, organization, authorization, MFA, OAuth/OIDC, audit, and administrative capabilities are intentionally reserved for their documented milestones.

## Technology baseline

- PHP 8.3.16 or newer in the 8.x line; the Docker runtime uses PHP 8.4
- Laravel 13
- PostgreSQL 18 with a shared schema
- Redis 8
- Vue 3, Inertia 3, TypeScript, Tailwind CSS 4, and shadcn-vue conventions
- Laravel DDD Toolkit with hexagonal modules
- PHPUnit, Pint, Larastan/PHPStan, Psalm with taint analysis, ESLint, and Playwright

## Local setup

Prerequisites:

- Docker Engine with Docker Compose
- `curl` and `openssl`

From a clean clone:

```bash
./bin/setup
```

The script creates an untracked `.env`, generates local-only secrets, builds the containers, installs locked dependencies, runs the empty migrations, starts the required services, and checks readiness.

Port 8080 is the default. If it is already in use, select another local port without editing tracked files:

```bash
APP_PORT=8088 APP_URL=http://localhost:8088 ./bin/setup
```

Services:

| Service | Address |
|---|---|
| Application | <http://127.0.0.1:8080> |
| Mailpit | <http://127.0.0.1:8025> |
| PostgreSQL | `127.0.0.1:5432` |
| Redis | `127.0.0.1:6379` |
| Vite, when enabled | `127.0.0.1:5173` |

Start or stop the default stack:

```bash
docker compose up -d --wait
docker compose down
```

Run Vite for frontend development:

```bash
docker compose --profile dev up -d node
```

PostgreSQL initialization creates a separate administrative role and application role, creates the `identity` database in UTF-8, sets the application role timezone to UTC, and enables `pg_stat_statements`. The initialization scripts run only when the PostgreSQL volume is first created.

## Verification

PHP commands run inside the application container:

```bash
docker compose run --rm app composer validate --strict
docker compose run --rm app php artisan ddd:check
docker compose run --rm app vendor/bin/pint --test
docker compose run --rm app vendor/bin/phpstan analyse
docker compose run --rm app composer security:taint
docker compose run --rm app vendor/bin/phpunit
docker compose run --rm app composer audit --locked
```

Frontend commands run inside the Node container:

```bash
docker compose run --rm --no-deps node npm ci
docker compose run --rm --no-deps node npm run lint
docker compose run --rm --no-deps node npm run typecheck
docker compose run --rm --no-deps node npm run build
docker compose run --rm --no-deps node npm audit
```

With the application stack running, execute Playwright in its browser image:

```bash
docker compose --profile test run --rm e2e
```

`composer security:taint-self-test` is present as an explicit guard and exits non-zero because the deliberately vulnerable fixture belongs to M12. It must not be treated as passing or added to CI before that milestone.

Health endpoints:

```text
GET /health/live
GET /health/ready
```

Liveness verifies the PHP process only. Readiness checks PostgreSQL and Redis, returns `503` when either is unavailable, and never returns connection details.

## Architecture

The repository contains nine empty capability shells under `app/Modules`:

```text
Installation  Identity      Organization
ModuleCatalog AccessControl Mfa
Session       OAuth         Audit
```

Each follows the Laravel DDD Toolkit hexagonal preset. Domain remains independent of Laravel and infrastructure; inter-module access must use application contracts or versioned integration events.

Read these sources before implementation:

1. accepted ADRs in [`docs/architecture/adrs`](docs/architecture/adrs);
2. [`docs/architecture/TRD-Laravel-DDD-Skeleton-v0.1.md`](docs/architecture/TRD-Laravel-DDD-Skeleton-v0.1.md);
3. [`docs/product/PRD-Identity-Platform-v0.1.md`](docs/product/PRD-Identity-Platform-v0.1.md);
4. [`docs/implementation/IMPLEMENTATION-PLAN.md`](docs/implementation/IMPLEMENTATION-PLAN.md).

Repository-wide guidance is in [`AGENTS.md`](AGENTS.md). Module-level guidance becomes more specific inside each module.

## License

This project is available under the [MIT License](LICENSE).
