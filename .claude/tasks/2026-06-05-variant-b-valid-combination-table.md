---
Status: planned
Created: 2026-06-05
---

## Goal
Implement the valid-combination (SKU-row) data model and a Doctrine-backed resolver
that derives options from the set of valid rows matching the selection.

## Context
Variant B of the VisionGroup assessment (the alternative compared against Variant A
in `docs/tradeoffs.md`). Depends on `foundation-api-platform-scaffold` being merged
to `main`. Lives on branch `variant-b-valid-combination-table` (off `main`,
independent of the Variant A branch). Implements
`App\Service\ParameterOptionResolverInterface` from the foundation and binds it as
the active resolver. Same `/parameter` path and JSON shape as the foundation.

## In scope
- Branch off `main` after the foundation task is merged (independent of Variant A).
- Entities (Processing, `src/Entity/`): `Parameter` + `ParameterOption` (as in
  Variant A), plus `ParameterCombination` — one row per valid tuple
  (`optionParameter1`, `optionParameter2`). No `ForbiddenCombination`.
- Migration (`doctrine:migrations:diff` + `:migrate`), committed.
- Fixtures seeding all 7 valid rows (9 combinations minus `(A,Y)`, `(C,Z)`).
- Repositories: `ParameterRepository`, `ParameterCombinationRepository`
  (filter rows by selection).
- `ValidCombinationParameterOptionResolver implements ParameterOptionResolverInterface`
  — filter valid rows by current selection; remaining options per parameter =
  distinct values across surviving rows. Bound as the active resolver.
- Tests: unit resolver (4 PDF cases + edges); integration repos (negative+happy);
  functional `/parameter` (4 PDF cases + 2× 400).

## Out of scope
- N>2 parameters; GraphQL, auth, frontend.
- Pushing to GitHub + emailing reviewers; presentation.

## Acceptance criteria
1. `GET /parameter` → `{"parameter1":["A","B","C"],"parameter2":["X","Y","Z"]}`.
2. `GET /parameter?parameter1=B` → `{"parameter1":["B"],"parameter2":["X","Y","Z"]}`.
3. `GET /parameter?parameter1=A` → `{"parameter1":["A"],"parameter2":["X","Z"]}`.
4. `GET /parameter?parameter2=Z` → `{"parameter1":["A","B"],"parameter2":["Z"]}`.
5. `GET /parameter?parameter1=Q` → 400.
6. `GET /parameter?parameter3=foo` → 400.
7. `make phpunit` green; all quality gates pass.

## Plan
- [ ] Step 1 — branch off main; entities + migration.
- [ ] Step 2 — fixtures (7 valid rows).
- [ ] Step 3 — repositories.
- [ ] Step 4 — `ValidCombinationParameterOptionResolver`; bind as active resolver.
- [ ] Step 5 — unit + integration + functional tests.
- [ ] Step 6 — quality gates + acceptance verification (6 curl cases live).
