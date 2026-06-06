---
Status: done
Created: 2026-06-05
Completed: 2026-06-05
---

## Goal
Scaffold the API Platform + Doctrine project, expose `/parameter` via a DTO
resource and state provider behind a resolver interface (with a stub
implementation), and ship the shared docs + test harness.

## Context
First of three tasks for the VisionGroup Symfony assessment
(`Symfony Assesment Task.pdf`, repo root). Builds the shared foundation on
**API Platform** (https://api-platform.com/); the two solution variants follow on
separate branches (`variant-a-constraint-list`, `variant-b-valid-combination-table`),
each implementing the resolver interface defined here. This task is on branch
`foundation-api-platform-scaffold`, merged to `main` before the variants branch off.

## Shared architecture (API Platform)
- Response shape `{ "parameter1": [...], "parameter2": [...] }` is custom → exposed
  resource is a plain DTO `ApiResource` (`src/ApiResource/ParameterOptions.php`).
- One `GetCollection` operation at path `/parameter`, served by a custom State
  Provider (`src/State/ParameterOptionsProvider.php`, `ProviderInterface`): reads
  selections from the query string, validates (unknown name/value →
  `BadRequestHttpException` → 400), delegates to the resolver, returns the DTO.
- Query params `parameter1`/`parameter2` declared as `QueryParameter` with
  `enum: [A,B,C]`/`[X,Y,Z]` so Swagger UI at `/api/docs` renders inputs + Execute.
- Seam: `App\Service\ParameterOptionResolverInterface::availableOptions(array $selection): array`
  — input `[name => value]`, output `[name => string[]]`.
- `/parameter` JSON must match the PDF exactly (no JSON-LD `@context`/`@id`) —
  restrict the operation to `json` or shape the DTO accordingly.
- Dataset: `parameter1=[A,B,C]`, `parameter2=[X,Y,Z]`; forbidden `(A,Y)`, `(C,Z)`.

## In scope
- `composer require api-platform/api-platform` (+ `doctrine/doctrine-migrations-bundle`
  if not transitive); dev: `doctrine/doctrine-fixtures-bundle`, `symfony/test-pack`.
- Doctrine DSNs: `.env` Postgres → compose `database:5432`; `.env.test` SQLite
  `sqlite:///%kernel.project_dir%/var/test.db` (`testing.md`). Rebuild image if
  `pdo_sqlite` missing.
- `src/ApiResource/ParameterOptions.php` — `#[ApiResource]` + `GetCollection` at
  `/parameter`, `parameters` = `QueryParameter` for `parameter1`/`parameter2`
  (enum), provider = `ParameterOptionsProvider`.
- `src/State/ParameterOptionsProvider.php` — read selections, validate (400),
  delegate to interface, return DTO; JSON shaping to PDF format.
- `src/Service/ParameterOptionResolverInterface.php` — the seam.
- `src/Service/StaticParameterOptionResolver.php` — stub returning the full
  unfiltered PDF dataset (no Doctrine); makes the endpoint boot + demonstrable.
- `README.md` — overview, link to PDF, `docker compose up` setup, four PDF curl
  examples (note filtering arrives on variant branches), `/api/docs` link,
  `docs/tradeoffs.md` link, branch map.
- `docs/tradeoffs.md` — PDF step 2: Variant A (constraint list, chosen) vs
  Variant B (valid-SKU table); pros/cons; why A; N-parameter as future work.
- Functional test harness + smoke test (criterion F1; `/api/docs` reachable).

## Out of scope
- Real filtering logic / Doctrine entities (delivered by variant tasks).
- N>2 parameters; GraphQL, auth, rate limiting, frontend, admin.
- Hand-written static `openapi.yaml` (API Platform generates it).
- Pushing to GitHub + emailing reviewers (PDF steps 4–5); presentation (step 5).

## Acceptance criteria
1. `GET /parameter` → `{"parameter1":["A","B","C"],"parameter2":["X","Y","Z"]}`.
2. `https://localhost/api/docs` renders Swagger UI with `/parameter` +
   `parameter1`/`parameter2` query inputs.
3. `README.md` and `docs/tradeoffs.md` present and accurate.
4. `make phpunit` green; all quality gates pass.

## Plan
- [x] Step 1 — install API Platform + dev deps; boot container; `/api/docs` loads.
- [x] Step 2 — wire Doctrine DSNs (`.env`, `.env.test`); rebuild image if needed.
- [x] Step 3 — `ParameterOptionResolverInterface` + `StaticParameterOptionResolver`.
- [x] Step 4 — `ParameterOptions` DTO resource + `GetCollection` + QueryParameters.
- [x] Step 5 — `ParameterOptionsProvider` (validation, 400, JSON shaping).
- [x] Step 6 — `README.md` + `docs/tradeoffs.md`.
- [x] Step 7 — functional smoke test + harness.
- [x] Step 8 — quality gates (`quality-gates.md`).
