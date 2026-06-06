# Testing Requirements

Tests are expected after most implementation tasks. The table below is the single source of truth for when a new test is required and when existing coverage is enough — `quality-gates.md` gate 7 defers to it.

## When tests are required

| Task type | Required test type |
|---|---|
| New or modified service | Unit test |
| New or modified transformer | Unit test |
| New or modified command | Unit test for logic; integration test if the command's correctness depends on actual DB state (idempotency, duplicate detection, ordering) |
| New or modified repository method | Integration test (negative path first, then happy path) |
| New controller | Functional test (WebTestCase) |
| New or modified user-facing feature (page, interactive map, filter, modal, mobile sheet) | Playwright E2E spec (`tests/Playwright/specs/<feature>.spec.js`) covering happy path + key failure modes on both desktop and mobile. Single-browser (chromium). No screenshot comparisons. |
| Pure refactor (no logic change) | Existing tests must still pass; no new tests required |

## Test structure

- `tests/Unit/` mirrors `src/` — e.g. `src/Service/FuelPriceImporter.php` → `tests/Unit/Service/FuelPriceImporterTest.php`
- `tests/Integration/` for anything touching the database
- Class name: `{ClassName}Test`
- Method name: `test{Action}{Scenario}` — e.g. `testImportWithDuplicateRowsSkipsExisting`
- Internal structure: **Arrange / Act / Assert** — one assertion concept per test method

## Test database

Integration tests run against **SQLite in-memory**, not Postgres. Configured via `DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.db"` in `.env.test`. This matches the CI environment.

- Do not use Postgres-specific syntax (`ILIKE`, `::cast`, array types) in integration tests — it passes locally but fails in CI.
- Run integration tests: `docker compose exec php vendor/bin/phpunit --testsuite Integration`
- Run unit tests: `docker compose exec php vendor/bin/phpunit --testsuite Unit`

## Coverage priority

**Negative paths first, happy paths second.** Failures are the most visible production problems; test the failure modes before the success case.

Every public method on a repository must have at least one negative-path integration test and one happy-path integration test.

## What to mock

- Mock at interface boundaries only: SharePoint client, HTTP clients, external APIs.
- External dependencies that cross a system boundary (SharePoint, HTTP, clock, filesystem) must be defined behind an interface — this satisfies the KISS interface justification and enables mocking without concrete class coupling.
- Never mock Symfony internals (container, router, event dispatcher). Use Symfony's provided testers instead: `CommandTester` for commands, `WebTestCase` / `KernelBrowser` for controllers, `self::getContainer()` for services in integration tests.
- Never mock the class under test itself.
- Do not mock collaborators that are fast and side-effect-free (e.g. value objects, pure transformers).

## What NOT to test

- Symfony wiring (routing, service registration, container config)
- Plain getters/setters on entities with no logic
- Third-party library behavior
