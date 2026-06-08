# Option-Selection Resolver: Variant A vs Variant B

## Overview

Two branches implement the `ParameterOptionResolverInterface::availableOptions()` contract (defined on `main`) to power the `GET /parameter` endpoint. **Both solve the same assignment: given a partial parameter selection, return the remaining available option values per parameter.** They are competing solutions, not complementary pieces, distinguished by their constraint representation and architectural choices.

- **Variant A** (`variant-a-constraint-list`): Blocklist — stores forbidden option pairs; resolver rejects options that conflict with a selection.
- **Variant B** (`variant-b-valid-combination-table`): Allowlist (SKU table) — stores valid option pairs; resolver filters to valid rows and projects results.

### Domain Context

The real problem domain is **contact-lens parameter selection**: customers select lens power, base curve, diameter, and other optical/physical parameters **sequentially**, with the constraint that **certain combinations aren't manufacturable** due to supply-chain or production limits. The abstract `parameter1` and `parameter2` in this API are stand-ins for at least 3–4 real lens parameters. Both variants implement the same contract — they differ in how they express and enforce the "non-manufacturable" constraint set, and crucially, in how they scale as the parameter count grows.

## Comparison

| Aspect | Variant A (Blocklist) | Variant B (Allowlist/SKU Table) |
|--------|----------------------|--------------------------------|
| **Data Model** | `ForbiddenCombination(optionA, optionB)` entity stores prohibited pairs | `ParameterCombination(option1, option2)` entity stores allowed pairs |
| **Constraint Representation** | Negative constraint: "A and X cannot coexist" | Positive constraint: "only these A↔X pairs are valid" |
| **Resolver Logic** | Iterate options; reject those that form a forbidden pair with any selection | Filter valid combinations by selection; project distinct values per column |
| **Repository Method** | `ForbiddenCombinationRepository::isForbidden(optionA, optionB): bool` — point lookup | `ParameterCombinationRepository::findBySelection(slots): ParameterCombination[]` — set query |
| **ParameterOption Entity** | Includes `position: int` field for ordered display | No position field |
| **ParameterOptionsProvider** | **Refactored** — DB-driven validation via `ParameterCatalog::validValues()`; injects selection via resolver | **Refactored** — calls `availableOptions([])` to discover valid parameters; validation from resolver |
| **ParameterOptions API Resource** | Enum constraints injected dynamically from DB by `ParameterEnumDecorator` (metadata factory); OpenAPI contract preserved | Removes static enums; relies on resolver results; looser OpenAPI contract |
| **ParameterCatalog & ParameterEnumDecorator** | Variant A adds these to couple DB to OpenAPI metadata at cache-warmup time | N/A |
| **Files Added/Touched** | 10 new files; 15 existing modified | 8 new files; 15 existing modified |

## Architecture Fit

### Layer Dependencies (Provider → Service → Repository)

Both variants respect the layer boundary. `ParameterOptionResolverInterface` (processing layer) is injected into `ParameterOptionsProvider` (presentation layer), which delegates validation and option filtering to the resolver. Controllers are thin. ✓

**Variant A difference:** `ParameterOptionsProvider` (refactored) delegates configuration to `ParameterCatalog`, which reads all parameters and their options from the database. OpenAPI enums are injected at cache-warmup time by `ParameterEnumDecorator` — a `ResourceMetadataCollectionFactoryInterface` that decorates the metadata factory. This means the OpenAPI contract is kept in sync with the DB automatically, and adding a third parameter requires only DB and entity changes, not code changes.

**Variant B difference:** `ParameterOptionsProvider` (refactored) calls `$resolver->availableOptions([])` to learn which parameters and values exist, then validates dynamically. Configuration lives in fixtures/DB, not hardcoded. More flexible but adds a resolver call per request (even empty selection). See `src/State/ParameterOptionsProvider.php` in `variant-b-valid-combination-table`.

### API Platform Idioms

The `ParameterOptions` resource on `main` declares enum constraints on `parameter1` and `parameter2` (see `src/ApiResource/ParameterOptions.php`). These are the API's source of truth for valid values.

**Variant A:** Uses `ParameterCatalog` (reads from DB) to drive the provider's validation, and decorates the OpenAPI metadata with enum constraints injected by `ParameterEnumDecorator` at cache-warmup time. This unifies the source of truth: the database. Both validation and OpenAPI contracts come from the same DB data, so divergence between validation and filtering is impossible — the resolver and the provider are bound to the same DB state.

**Variant B:** Inverts the dependency — the resolver (DB data) becomes the source of truth (see refactored `src/ApiResource/ParameterOptions.php` and `src/State/ParameterOptionsProvider.php`). API enums must be kept in sync by hand or removed. This makes the API contract depend on resolver state, which is non-standard.

### KISS Guardrails

**Variant A:**
- One new aggregate `ForbiddenCombination` (entity + repository), justified for constraint storage. ✓
- Two new service classes: `ParameterCatalog` (reads DB for valid values) and `ParameterEnumDecorator` (metadata factory to inject OpenAPI enums at cache-warmup time). Justified for decoupling configuration from code. ✓
- Provider and resolver remain simple; resolver is straightforward iteration through `src/Service/ConstraintListParameterOptionResolver.php`. ✓
- Upside: configuration is DB-driven and OpenAPI contract is kept in sync automatically.

**Variant B:**
- One new aggregate `ParameterCombination` (entity + repository), justified for allowlist storage. ✓
- Refactors provider — now calls resolver to discover valid parameters dynamically. Adds indirection in `src/State/ParameterOptionsProvider.php`.
- Modifies `ParameterOptions` — removes enum constraints, shifts validation entirely to provider.

## Correctness

### Filtering Logic

**Variant A:** `ConstraintListParameterOptionResolver::isOptionAvailable()` (lines 62-91 in `src/Service/ConstraintListParameterOptionResolver.php`) checks if a candidate option forms any forbidden pair with a selected option. Uses `$this->forbiddenRepository->isForbidden($candidateOption, $selectedOption) || $this->forbiddenRepository->isForbidden($selectedOption, $candidateOption)` (lines 82-85) — dual-direction lookup handles both orderings. No symmetry assumption: a single stored direction suffices.

**Variant B:** `ValidCombinationParameterOptionResolver::availableOptions()` (see `src/Service/ValidCombinationParameterOptionResolver.php`) filters all combinations matching the selection via `findBySelection()`, then projects distinct values. No symmetry assumption — allowlists are inherently one-directional. However, **assumes parameters are positional and map to columns `option1` and `option2` only** (lines ~24-26: `$nameToSlot[$parameter->getName()] = $slot`, with comment "Parameters are positional"). Does not scale to 3+ parameters without redesign.

### Edge Cases

**Empty selection:**
- **Variant A:** Returns all options (resolver iterates parameters, finds no conflicts).
- **Variant B:** Returns all options (filter returns all rows, projects all values).
Both correct. ✓

**Invalid parameter name:**
- **Variant A:** Provider rejects with 422 (checks against `ParameterCatalog::validValues()` in `src/State/ParameterOptionsProvider.php:35-47`, throws `UnprocessableEntityHttpException`).
- **Variant B:** Provider rejects with 400 (checks against resolver's result in refactored `ParameterOptionsProvider`, throws `BadRequestHttpException`).
Both correct. ✓

**Invalid option value:**
- **Variant A:** Provider rejects with 422 (checks against `ParameterCatalog::validValues()`, throws `UnprocessableEntityHttpException`).
- **Variant B:** Provider rejects with 400 (checks against resolver's result, throws `BadRequestHttpException`).
Both correct. ✓

**Constraint direction and completeness:**
- **Variant A:** `ConstraintListParameterOptionResolver::isOptionAvailable()` (lines 82-85 in `src/Service/ConstraintListParameterOptionResolver.php`) checks both directions: `isForbidden($candidateOption, $selectedOption) || isForbidden($selectedOption, $candidateOption)`. This means a single stored direction (e.g., A→X) is sufficient — the dual-direction query covers all cases. No symmetry assumption in the DB; no correctness liability. ✓
- **Variant B:** Allowlist is exhaustive by definition — all valid pairs are explicit. No implicit direction or symmetry assumptions. ✓
**Both correct, by different approaches.** ✓

## Data-Model Scalability

### Sequential Selection and Partial-Selection Filtering

The contact-lens domain requires **sequential parameter selection**: customers narrow their choices parameter-by-parameter, and the API must return available options for the *next* parameter given the *current* selection (partial, not fully specified). Both variants already satisfy this via the `availableOptions(array $selection)` contract:

- **Variant A:** Iterates through all parameters; for each, filters options by checking if they conflict with any selected option (via the forbidden-pair lookup). Returns only the non-conflicting options for each parameter.
- **Variant B:** Filters the valid-combination rows to those matching the partial selection (by slot), then projects distinct values per column. Returns only the values that appear in matching rows.

Both handle partial selection naturally — the contract supports incremental narrowing without re-specification. ✓

### Manufacturability as Constraint Semantics

From a domain perspective, "non-manufacturable" combinations are exactly the **complement of the valid SKU inventory** — i.e., every combination *not* in the physical/supply master is forbidden. This semantics aligns naturally with:

- **Variant B (Allowlist/SKU table):** A row in `ParameterCombination` = a physically-manufacturable combination. The allowlist is the direct inverse of "non-manufacturable." Adding real lens combinations means populating the SKU table. This mirrors how physical SKU masters work in manufacturing systems.
- **Variant A (Blocklist):** Stores forbidden pairs/tuples explicitly. Requires the data team to compute and store the inverse — i.e., enumerate constraints rather than valid combinations. Suitable when forbidden combinations are sparse; unsuitable when the valid set is small and the forbidden set is large.

For contact lenses with restrictive manufacturability constraints (few valid combinations out of many possible), **Variant B's SKU-table semantics is a better domain fit**. However, implementation matters; see next section.

### Pairwise vs N-ary Constraints

Current assignment: 2 parameters. Both model pairwise constraints.

**Variant A — Blocklist:**
- Adding parameter3 requires storing forbidden *triples*. Entity and resolver logic change.
- Blocklists encode negative constraints ("don't pair A and X"), naturally sparse in practice.
- Memory: **O(F)** where F = forbidden pairs (sparse).

**Variant B — Allowlist/SKU table:**
- Adding parameter3 requires adding option3 column to `ParameterCombination` (see `src/Entity/ParameterCombination.php`). Resolver's positional-slot mapping (lines ~12-19) must also change — currently hardcoded to 2 slots.
- Allowlists encode valid configurations ("only these combinations work"), can be dense or sparse.
- Memory: **O(V)** where V = valid combinations.

### Positional Slot Assumption (Variant B's Bottleneck)

Variant B's resolver (`src/Service/ValidCombinationParameterOptionResolver.php`, lines ~17-26):
```php
$parameters = array_values($this->parameterRepository->findAll());
$nameToSlot = [];
foreach ($parameters as $slot => $parameter) {
    $nameToSlot[$parameter->getName()] = $slot;  // slot 0, 1, ...
}
```

Assumes:
1. Parameters have stable order.
2. Each parameter maps to exactly one slot (0 or 1).
3. Fixed slot count (2).

**A third parameter breaks the design:** the DB has no option3 column. Full refactor required (restructure `ParameterCombination` or introduce a junction table).

**Variant A has no positional assumption.** `isForbidden()` works for any number of parameters if constraint pairs are in the DB. ✓

### Sparse vs Dense Valid Sets and the N-Parameter Trade-off

- **Blocklist (A):** Efficient when most combinations are valid (sparse forbidden set). Example: 100 options; only 5 forbidden pairs. Scales structurally to N parameters — extend from pairs to N-tuples — but remains **pairwise-only** in the simplest form (forbidden 2-option interactions). Expressing higher-order constraints (e.g., "A + B + C together are invalid") requires redesign to support N-ary tuples.
- **Allowlist (B):** Efficient when most combinations are invalid (sparse valid set). Example: only 20 valid configs out of 10^10 possible. Inherently captures **full N-dimensional constraints** — a single row encodes a valid combination of all parameters, so higher-order constraints are implicit. However, the current 2-slot implementation **hard-limits scaling** to 2 parameters; adding a third requires restructuring `ParameterCombination` (new column) and the resolver's positional mapping.

**The trade-off for contact lenses:**

Contact-lens manufacturability is inherently **N-dimensional** (at least 3–4 parameters). The domain favors:
- **Variant B semantically** — a SKU row naturally captures the full manufacturable combination, avoiding the need to decompose constraints into pairwise forbidden pairs. But **Variant B's 2-slot implementation is a blocker** — scaling to 3+ parameters requires a full refactor (junction table or JSON column).
- **Variant A structurally** — no positional cap; extends to any parameter count. But requires **manual N-ary constraint redesign** — moving from pairwise forbidden pairs to N-tuples is a code and schema change.

**The open question:** For the next phase (3+ lens parameters), is it better to (a) redesign Variant B's SKU table to support N columns / junction table (preserving the natural "row = valid combo" semantics), or (b) extend Variant A's blocklist to N-ary tuples (preserving the pairwise-lookup infrastructure but adding constraint decomposition complexity)? Neither variant as currently implemented is a clean fit for N-parameter scaling.

## SOLID Adherence

### SRP (Single Responsibility Principle)

**Variant A:**
- `ParameterCatalog` (value discovery) and `ConstraintListParameterOptionResolver` (selection resolution) represent a clean split of concerns.
- However, the resolver is overloaded: it contains duplicated find-option-by-value loops, re-loads parameters and performs N+1 repository lookups in `isOptionAvailable()` (`src/Service/ConstraintListParameterOptionResolver.php:71`), and exceeds the 3-level nesting limit prescribed in `code-standards.md` §3.

**Variant B:**
- The resolver itself is thin and focused.
- However, `ParameterCombinationRepository::getDistinctValues()` (`src/Repository/ParameterCombinationRepository.php:55`) is pure in-memory aggregation over already-fetched entities living inside a persistence class — a layering/SRP smell. This logic belongs in the resolver or a dedicated transformer, not a repository.

### OCP (Open/Closed Principle)

- The `ParameterOptionResolverInterface` seam gives both branches clean strategy extensibility.
- **Variant A:** The forbidden-pair model scales to N parameters with no schema change — constraint tuples of any arity can coexist in the DB.
- **Variant B:** Hard-capped at 2 parameters by the fixed `option1`/`option2` columns and the resolver's positional slot logic (`src/Service/ValidCombinationParameterOptionResolver.php:21`, `src/Repository/ParameterCombinationRepository.php:40`). Adding a third parameter requires modifying closed code/schema — an OCP violation at the data-model level.
- **Shared ceiling:** `ParameterOptions` DTO and both providers hardcode `parameter1`/`parameter2`, capping the presentation layer at 2 parameters regardless of resolver generality.

### LSP (Liskov Substitution Principle)

- **Variant A:** `ParameterCatalog` serves as the independent authority on valid parameters and values. The resolver never doubles as a catalog.
- **Variant B:** The provider treats `availableOptions([])` as the authoritative catalog of valid params/values (`src/State/ParameterOptionsProvider.php:30`) — a behavior the interface never explicitly promises. Swapping in a resolver that returns `[]` for empty input breaks the provider's validation logic. This is a fragile contract dependency.
- **Shared weakness:** `StaticParameterOptionResolver` ignores `$selection` entirely, remaining type-conformant while behaviorally diverging from the interface's documented intent.

### ISP (Interface Segregation Principle)

- Both variants employ a single-method interface with no fat contract ✓.
- **Variant A:** Separates value-discovery (`ParameterCatalog`) from selection-resolution (`ParameterOptionResolverInterface`), aligning each abstraction with a single client role.
- **Variant B:** Overloads one resolver method for two distinct client roles — "discover what parameters/values exist" (provider validation) and "filter options given a selection" (provider response). A single method serving two unrelated purposes is a slight ISP smell.

### DIP (Dependency Inversion Principle)

- **Both variants:** Essentially equal. Each provider depends on the resolver **abstraction** with concrete implementations injected via constructor DI ✓.
- **Variant A:** `ParameterEnumDecorator` is a textbook decorator over `ResourceMetadataCollectionFactoryInterface`, inverting the dependency from the metadata-injection concern to an abstraction ✓.
- **Both:** Resolvers depend on concrete Doctrine repositories — acceptable in this stack per the KISS rule (no interface until 2+ implementations; Doctrine repositories are concretes by convention). Not a SOLID violation.

### Summary

| Principle | Variant A (Blocklist) | Variant B (Allowlist/SKU table) |
|---|---|---|
| **SRP** | split services ✓ / complex resolver ✗ | thin resolver ✓ / aggregation in repo ✗ |
| **OCP** | scales to N params ✓ | capped at 2 by schema ✗ |
| **LSP** | catalog independent ✓ | provider leans on un-contracted behavior ✗ |
| **ISP** | catalog/resolver split ✓ | one method, two roles ✗ |
| **DIP** | ✓ | ✓ |

**Bottom line:** Both variants adhere to SOLID's core intent — strong resolver abstraction, constructor DI, clean layer separation. Variant A adheres more faithfully: better SRP/ISP separation and a domain model structurally open to N parameters. Its cost is internal resolver complexity (a KISS/readability concern, not a structural SOLID violation). Variant B is simpler but carries genuine OCP violations (2-parameter schema cap) and LSP/ISP smells (single abstraction overloaded for two roles). This reinforces the existing Variant A recommendation.

## Recommendation: **Variant A (Constraint List / Blocklist)**

**Rationale:**

1. **Correctness:** Both variants are correct. Variant A's dual-direction lookup (`isForbidden($a, $b) || isForbidden($b, $a)`) handles constraint pairs robustly — no symmetry assumption in the DB, no hidden correctness liability. Variant B's allowlist is also self-contained and correct. On correctness, **parity.** ✓

2. **Architecture Fit:** Both variants now employ DB-driven validation via service classes (Variant A: `ParameterCatalog` + `ParameterEnumDecorator`; Variant B: direct resolver queries in the provider). **Variant A preserves the OpenAPI enum contract naturally** — `ParameterEnumDecorator` injects enums into the API metadata at cache-warmup time, keeping the OpenAPI schema in sync with the DB. Variant B removes enum constraints from `ParameterOptions` and requires ad-hoc schema modification if strict validation is needed. **Variant A wins on API contract integrity.** ✓

3. **Data-Model Scalability:** This is an **open trade-off**. Variant A structurally scales to N parameters (no positional cap; a forbidden tuple can have any arity), but requires extension from pairwise to N-ary tuples to express full constraints. Variant B semantically fits the domain (a SKU row naturally represents a full N-dimensional manufacturable combination), but its 2-slot implementation hard-caps at 2 parameters — scaling to 3+ requires restructuring `ParameterCombination` (new columns or a junction table). **For the current 2-parameter assignment, Variant A is ready to deploy.** For future N-parameter phases, neither variant as implemented is a clean fit; the next phase must choose between (a) redesigning B's SKU table to support N dimensions, or (b) extending A's blocklist to N-ary constraints. ✓

**Reconciliation with README:** The `README.md` on `main` states "Variant A was chosen." This recommendation aligns with that choice. The prior tradeoffs analysis, written before Variant A's `4693e24` refactor (DB-driven validation), incorrectly flagged A as having "hardcoded coupling" and derived a recommendation for B. The corrected evidence supports A: decoupled configuration (via `ParameterCatalog`), preserved API contracts (via `ParameterEnumDecorator`), and better scalability (no positional limits).

### Implementation Path (Future)

- Merge `variant-a-constraint-list` into `main` (aligns with README's "Variant A was chosen").
- Update `README.md` to explain why Variant A was chosen and link to this document for full analysis.
- If decoupling `ParameterEnumDecorator` from the resolver is desired, define an interface `ParameterValidationSourceInterface` to abstract parameter/option discovery, allowing the API metadata decorator and the resolver to share the same interface (optional refactor, not a blocker).
