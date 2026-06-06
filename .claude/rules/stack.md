# Stack & Commands

## Stack

- **Symfony 8.0** (skeleton) running under **FrankenPHP** worker mode on Caddy, via the [`dunglas/symfony-docker`](https://github.com/dunglas/symfony-docker) template. All PHP/Composer execution happens **inside the `php` container** — there is no host PHP/composer requirement.
- Default service: `php` (name in `compose.yaml`). Exposes `:80` (HTTP), `:443` (HTTPS + HTTP/3). TLS cert is self-signed on first run.
- Dev override (`compose.override.yaml`) mounts the project at `/app` and puts `vendor/` on a named volume to avoid slow/racey bind-mount I/O on macOS. Do not remove that `- /app/vendor` line without good reason.
- **Database**: PostgreSQL 18 (alpine) via the `database` compose service. Default credentials in `.env`: user `app`, password `!ChangeMe!`, db `app`. Reachable from the `php` container at host `database:5432`. Doctrine ORM + migrations bundle are installed. The `pdo_pgsql` extension is baked into the image (see Dockerfile `###> doctrine/doctrine-bundle ###` block) — **if you change DB packages, rebuild the image** (`docker compose build php`).
- **Test database**: Integration tests use SQLite in-memory, not Postgres. Configured via `DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.db"` in `.env.test`. This matches CI. Do not write Postgres-specific SQL in integration tests.

## Commands

All commands assume you are at the project root.

- `docker compose build --pull` — build/rebuild images.
- `docker compose up --wait` — start; first run scaffolds the Symfony skeleton via `frankenphp/docker-entrypoint.sh`. Browse `https://localhost` and accept the self-signed cert.
- `docker compose down --remove-orphans` — stop.
- `docker compose exec php bin/console <cmd>` — run any Symfony console command (e.g. `--version`, `debug:router`, `cache:clear`).
- `docker compose exec php composer <cmd>` — run Composer inside the container (e.g. `composer require symfony/scheduler`).
- `docker compose exec php bin/console dbal:run-sql "SELECT 1"` — sanity-check DB connectivity.
- `docker compose exec php bin/console doctrine:migrations:migrate --no-interaction` — apply migrations. `doctrine:migrations:diff` generates a new migration from entity changes.
- `docker compose exec database psql -U app app` — open a psql shell against the dev DB.
- `docker compose exec php vendor/bin/phpunit <path>` — run tests. Single test: append `--filter MethodName path/to/TestFile.php`.
- `docker compose logs -f php` — tail app logs.

**If `docker compose up` fails once during first-run scaffolding, retry it before investigating.** Composer package extraction occasionally races on macOS bind mounts and self-heals on retry.
