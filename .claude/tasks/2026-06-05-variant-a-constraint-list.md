---
Status: planned
Created: 2026-06-05
---

## Goal
Implement the forbidden-pair (constraint list) data model and a Doctrine-backed
resolver that filters options against stored forbidden combinations.

## Context
Variant A of the VisionGroup assessment (the **chosen** solution per
`docs/tradeoffs.md`). Depends on `foundation-api-platform-scaffold` being merged to
`main`. Lives on branch `variant-a-constraint-list` (off `main`). Implements
`App\Service\ParameterOptionResolverInterface` from the foundation and binds it as
the active resolver, replacing the foundation stub on this branch. Same `/parameter`
path and JSON shape as the foundation.

## In scope
- Branch off `main` after the foundation task is merged.
- Entities (Processing, `src/Entity/`): `Parameter` (name + ordered
  `ParameterOption`s), `ParameterOption` (value, position, FK),
  `ForbiddenCombination` (two `ParameterOption` refs: `optionA`, `optionB`).
- Migration (`doctrine:migrations:diff` + `:migrate`), committed.
- Fixtures seeding the PDF dataset incl. forbidden `(A,Y)`, `(C,Z)`.
- Repositories: `ParameterRepository`, `ForbiddenCombinationRepository` (minimal
  read methods).
- `ConstraintListParameterOptionResolver implements ParameterOptionResolverInterface`
  — for each parameter, candidate value `v` included iff no forbidden combination
  matches `(P=v, ...selection)`; selected parameter → `[selectedValue]`. Bound as
  the active resolver.
- Tests: unit resolver (4 PDF cases + edges); integration repos (negative+happy per
  `testing.md`); functional `/parameter` (4 PDF cases + `?parameter1=Q` 400,
  `?parameter3=foo` 400).

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
- [ ] Step 2 — fixtures (PDF dataset + forbidden pairs).
- [ ] Step 3 — repositories.
- [ ] Step 4 — `ConstraintListParameterOptionResolver`; bind as active resolver.
- [ ] Step 5 — unit + integration + functional tests.
- [ ] Step 6 — quality gates + acceptance verification (6 curl cases live).
