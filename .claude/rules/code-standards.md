# Code Standards

Single source of truth for how PHP code in `src/` must be structured. Covers three concerns: which layer a class belongs to, how it's written (Symfony conventions), and how simple it must stay (KISS).

---

## 1. Layer dependency rules

The application has three layers. Data flows in one direction only.

```
Ingestion (SharePoint client, scheduled commands)
    ↓
Processing / Domain (services, transformers, repositories, entities)
    ↓
Presentation (controllers, templates)
```

### Allowed dependencies

```
Controller  →  Service  →  Repository
Command     →  Service  →  Repository
Handler     →  Service  →  Repository
```

Commands may live in Ingestion (scheduled downloaders) or serve as admin CLI tools. The rule is the same: Command → Service only, never Command → Repository directly.

### Never allowed

- Service → Controller
- Repository → Service
- Presentation layer → Ingestion layer (or reverse)
- Skipping a layer (Controller → Repository directly)

### Entities

Entities belong to the Processing layer. Any layer may **read** an entity; only Processing may **mutate** one (`$em->persist/flush`).

### Event listeners and subscribers

Listeners and subscribers belong to the layer of the event they react to:

- Doctrine lifecycle callbacks (`PostPersist`, `PreUpdate`, etc.) → Processing
- Kernel/HTTP events (`KernelEvents::REQUEST`, `KernelEvents::RESPONSE`) → Presentation
- Custom domain events → Processing

### Enforcement

A layer violation is an automatic task failure. Restructure before proceeding — do not note it as "acceptable for now". If a genuine edge case requires bending the rule, log the exception and justification in `task_outcomes_log`.

---

## 2. Symfony coding conventions

Applies to all PHP files in `src/`. No exceptions without explicit justification.

### Dependency injection

- Constructor injection only. Never `$this->get()`, `$this->container`, or `@inject`.
- Declare all constructor parameters `private readonly`.
- Services are autowired. Add manual definitions in `services.yaml` only when autowiring cannot resolve ambiguity.

### Attributes over configuration

- Routing: `#[Route]` attributes. No YAML/XML routes.
- Doctrine: `#[ORM\...]` attributes. No YAML/XML mapping.
- Commands: `#[AsCommand]`. Handlers: `#[AsMessageHandler]`. Schedules: `#[AsSchedule]`.

### Environment & secrets

- Access env vars via `%env(VAR)%` in config files only.
- Never use `$_ENV`, `$_SERVER`, or `getenv()` directly in PHP classes.
- Never hardcode credentials, DSNs, tokens, or API keys anywhere in `src/`.
- Secrets belong in `.env.local` (not committed) or container environment variables.

### Code style

- `declare(strict_types=1)` at the top of every PHP file.
- Return type declarations required on all methods.
- Use `readonly` for value objects and DTOs that must not change after construction.
- No `dump()`, `dd()`, `var_dump()`, or `print_r()` in committed code.
- PSR-12 enforced by phpcs — run before committing.

### Persistence

- All DB queries go through Doctrine repositories. No raw SQL in services or controllers.
- Never call `$em->flush()` from outside a service — controllers, commands, and handlers delegate to services.
- Every schema change needs a migration (`doctrine:migrations:diff` then review before committing).

### Logging & errors

- Use `LoggerInterface` for all logging. Never `error_log()`.
- Log context arrays — never interpolate variables into the message string.
- Exceptions bubble up; catch only when you can handle or add context.

### Controllers

- Controllers must be thin: validate input, call a service, return a response. No business logic.
- Return `Response` objects — never `echo` or `print`.

---

## 3. KISS guardrails

Apply KISS (Keep It Simple) aggressively. Challenge every abstraction before introducing it.

### Patterns requiring justification

| Pattern | Allowed only when |
|---|---|
| Interface | 2+ concrete implementations exist NOW, or a test mock requires it |
| Abstract class | 3+ concrete subclasses share non-trivial behavior |
| Event | 2+ independent listeners need to react |
| Factory | Construction requires conditional logic or 3+ chained steps |
| Builder | Same justification as Factory — a Builder is a Factory with a fluent interface; apply identical criteria |
| Value object | It carries validation or behavior — not just data grouping |
| Custom collection | Never — use typed arrays or native array functions |

### Complexity limits

- Max **5 constructor parameters**. Loggers, clocks, and similar cross-cutting dependencies still count — if you need more than 5, the class has too many responsibilities and must be split.
- Max **3 levels of nesting** (if / foreach / match) — extract a method instead.
- Max **150 lines per class** — if longer, split responsibilities.

### Naming

Use role-describing suffixes. Forbidden suffixes: `*Manager`, `*Helper`, `*Util`, `*Facade`, `*Proxy`.

Allowed: `Importer`, `Normalizer`, `Repository`, `Fetcher`, `Command`, `Handler`, `Transformer`, `Validator`, `Builder` (only when construction is genuinely complex).

### Escape hatch

A limit above may be bent when splitting would hurt clarity more than the violation does. This is rare. When it happens, record it in `task_outcomes_log` as: *"KISS exception: &lt;rule&gt; in &lt;class&gt; — &lt;reason&gt;."* No silent violations.


### Tools available for agents for project debuging and testing
@./../../mate/AGENT_INSTRUCTIONS.md