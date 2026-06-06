# Frontend

Single source of truth for how the browser-side of this project is built. Covers the stack choice, Stimulus conventions, Twig handoff, asset layout, CSS, accessibility, build commands, testing, and KISS/forbidden guardrails.

The Kuras fuel-price map (`.claude/tasks/completed/2026-04-23-kuras-map-frontend.md`) is the first user of these rules — referenced below as an exemplar, never duplicated.

---

## 1. Stack

The frontend stack is fixed:

- **Webpack Encore** — bundler. Not AssetMapper.
- **Symfony UX Stimulus** — Stimulus controllers registered via `@symfony/stimulus-bridge` (JS) and `symfony/stimulus-bundle` (PHP).
- **Twig** — server-rendered templates. Stimulus progressively enhances the markup Twig emits.
- **Plain CSS** — no preprocessor, no framework, no CSS-in-JS.

### Why this stack

- Project intent is a server-rendered site that progressively enhances interactive pieces. Stimulus is the textbook fit.
- Infra is already provisioned: Encore, `stimulus-bridge`, and a dedicated `node` container (see `stack.md` and `compose.dev.yaml`).
- The future headless API Platform project (separate app) can be consumed from Stimulus controllers via `fetch` when needed — no SPA framework required to "be ready for APIs".
- Server-rendered HTML stays crawlable without an SSR pipeline.

### Forbidden additions without an explicit decision in `decisions.md`

| If you're reaching for… | Don't. Use… |
|---|---|
| jQuery | Stimulus actions + vanilla DOM APIs |
| AssetMapper | Encore (switching is a migration, not a preference) |
| React / Vue / Svelte / Solid / Preact | Stimulus controllers |
| Symfony UX Live Components as default | Plain Stimulus; Live Components are case-by-case for server-driven forms, never for gesture-heavy UI |
| Redux / Zustand / MobX / Pinia | Stimulus values + outlets + CustomEvents |
| Client-side router (React Router, etc.) | Symfony routes + full page loads; add Turbo later if measurably needed |
| SCSS / Less / Tailwind / CSS-in-JS | Plain CSS with CSS custom properties |
| Service workers / PWA shell | None — revisit when offline is an actual product requirement |

---

## 2. Layer rules for the frontend

The frontend is the **Presentation layer**. Flow-of-dependency rules in `code-standards.md` §1 apply unchanged. Frontend-specific additions:

- Data handed to the frontend always originates from the Processing layer (a service or data provider). Twig is the seam. Stimulus never knows about entities directly.
- When the project starts consuming external HTTP APIs (e.g. the future API Platform app), those calls go through a **single JS data module per feature** (`assets/js/<feature>/api.js`) — not `fetch` calls scattered across controllers.
- Stimulus controllers do not write domain state to `localStorage`/`sessionStorage`. Storage is reserved for **UI preferences** (theme, onboarding dismissal). Domain state (filters, selections) lives in Stimulus values and the URL.

---

## 3. Stimulus controllers

### Topology

- One **root** controller per feature holds shared state and orchestrates leaves.
- **Leaf** controllers subscribe via Stimulus **outlets** (preferred — typed, explicit references) or CustomEvents for window-scoped signals.
- Event names are namespaced: `<feature>:<event>` — e.g. `kuras:filters-changed`, `kuras:viewport-changed`.
- No ad-hoc global event bus. The controller tree IS the bus.

### File naming

- Path: `assets/controllers/<feature>_<role>_controller.js`
- Stimulus identifier is derived automatically: underscores → hyphens, `_controller.js` dropped.
- Example: `assets/controllers/kuras_map_controller.js` → `data-controller="kuras-map"`.

### Public API (inputs)

Use Stimulus primitives — don't invent parallel channels.

- **Values** (typed) — configuration and persistent state.
- **Targets** — references to child elements the controller manages.
- **Outlets** — typed references to other controllers.
- **Classes** — CSS class names the controller toggles.
- **Actions** — event bindings in markup.

Do not read `data-*` attributes directly in controller code.

### State placement

| Kind of state | Where it lives |
|---|---|
| Config / inputs supplied by Twig | Stimulus values (typed) |
| State that must survive a controller reconnect | Stimulus values |
| Transient (drag offset, animation tick, in-flight fetch handle) | Instance fields |
| Shareable via URL (selected station, active filters) | URL hash / query string — read in the root controller's `connect()` |
| UI preferences (theme, onboarding dismissal) | `localStorage`, key-prefixed with feature name |

### Forbidden

- `this.element.querySelector()` for elements that belong to another controller — use an **outlet**.
- Direct DOM mutation outside `this.element`'s subtree — dispatch a CustomEvent; the owning controller mutates.
- `window.<anything>` globals for cross-controller state.
- Manually attaching `window` / `document` listeners. Use Stimulus actions with the `@window` / `@document` modifier so teardown is automatic.

---

## 4. Twig ↔ Stimulus data handoff

- Pass data via typed Stimulus values serialised with `|json_encode`:

  ```twig
  <div data-controller="kuras-app"
       data-kuras-app-stations-value="{{ stations|json_encode }}">
  ```

- JSON payloads embedded in Twig should stay ≤ ~100 kB. Above that, fetch asynchronously from a dedicated Symfony controller action.
- DTOs passed to the frontend must mirror the shape of the future domain entity (see `code-standards.md` §1 on entities). The data provider's DTO is the contract — the frontend doesn't reshape it.
- Never render secrets, tokens, or user PII into `data-*` attributes (they're in the HTML source).
- Never use `|raw` to emit JSON. Always `|json_encode` — it escapes correctly for HTML attributes.

---

## 5. Asset layout

```
assets/
  app.js                        ← entry; imports styles + stimulus_bootstrap
  stimulus_bootstrap.js         ← stimulus-bridge registration
  controllers.json              ← Symfony UX package controller registrations
  controllers/                  ← one file per Stimulus controller
    <feature>_<role>_controller.js
  js/                           ← non-controller JS modules, grouped by feature
    <feature>/
      data.js                   ← seed data / API client
      helpers.js                ← pure functions (priceTier, cheapest, …)
      geometry.js               ← feature-specific constants (SVG paths, grids)
  styles/
    app.css                     ← global entry; imports feature stylesheets
    <feature>/
      index.css                 ← feature entry
      <component>.css           ← optional splits
```

Rules:

- One controller per concern. If a controller file exceeds **150 lines**, split responsibilities (same limit as `code-standards.md` §3).
- **Pure logic lives in `assets/js/<feature>/*.js`, not in controllers.** Controllers orchestrate the DOM; they don't compute. This also makes logic testable without a DOM.
- Controllers never `import` from another feature's `js/` folder. Cross-feature sharing goes in `assets/js/shared/` — and should be rare.

---

## 6. CSS conventions

- Plain CSS. No SCSS, Less, PostCSS plugins (beyond Encore defaults), Tailwind, or CSS-in-JS.
- **BEM-ish naming** for scoping: `.kuras-map`, `.kuras-map__pin`, `.kuras-map__pin--selected`.
- **Mobile-first**: base styles target mobile; use `@media (min-width: …)` to scale up.
- **Theming via CSS custom properties** declared on `:root` in `assets/styles/app.css`. Feature stylesheets read variables, don't redeclare palettes.
- **No ID selectors** for styling.
- **No `!important`** unless overriding a third-party rule with no alternative — document inline.
- Feature stylesheet import order is explicit in `app.css` — no auto-loading magic.

---

## 7. Accessibility baseline

- Custom interactive elements require `role`, `tabindex="0"`, and keyboard handlers (Enter/Space to activate; Arrow keys where ordering exists).
- Dialogs / bottom sheets: `role="dialog"` + `aria-modal="true"` when they cover primary content; focus trap while open; Escape dismisses.
- Form inputs have a `<label>` or `aria-label`. No bare inputs.
- Interactive SVG nodes (map pins, legend buttons) are focusable and actionable via keyboard.
- Prefer native `<button>`, `<a>`, `<input>` over `<div role="button">`.
- Colour is never the sole differentiator. Price tiers, statuses, and states add an icon or text cue in addition to colour.

---

## 8. Build & commands

All frontend tooling runs inside the dedicated `node` container declared in `compose.dev.yaml`. **Never run `npm` on the host.**

| Task | Command |
|---|---|
| Install node deps | `make assets-install` |
| Start asset watcher (rebuilds to `public/build/` on change) | `make assets-dev` |
| Production build | `make assets-build` |
| One-off npm / npx | `docker compose exec node npm <cmd>` |

- The node container runs `encore dev --watch`, rebuilding assets to `public/build/` on every change. Assets are served by FrankenPHP on `:443` — no separate port needed.
- `public/build/` is generated output. Never hand-edit. Verify it's gitignored before committing.

---

## 9. Testing

Backend test expectations (controllers, data providers, DTO factories) follow `testing.md` — no restatement here. Frontend-specific:

| Subject | How |
|---|---|
| Page render + Stimulus wiring (integration-level smoke) | **Playwright E2E tests** (`tests/Playwright/specs/`). Run via `npm run test:e2e` locally, or `npx playwright test` in CI. One `.spec.js` per functional scenario (navigation, filters, map interactions, detail panels, mobile sheets). Covers happy paths + key failures (e.g. empty results, filter edge cases). |
| Stimulus controller DOM interactions (unit-level) | **Manual browser verification** on desktop + mobile viewport if not covered by E2E. Record what was checked in the task outcome log. |
| Pure JS helper (priceTier, cheapest, clustering math) | **Optional.** If added, use Node-native `node --test` placed in `tests/Js/`. No Jest/Vitest unless JS volume clearly justifies a test framework. |

- Playwright tests run in CI on every MR + main. They block the pipeline on failure.
- A Symfony functional test of the page's controller (per `testing.md`) catches structure/wiring; Playwright catches rendering and interaction.
- The trigger to add new E2E specs is a new interactive feature (filter, pan/zoom, modal, etc.) — add a `.spec.js` that exercises the user workflow.

---

## 10. KISS guardrails

| Pattern | Allowed only when |
|---|---|
| New Stimulus controller | The concern isn't served by an existing controller, AND the controller stays under the 150-line / 5-value / 5-outlet limit |
| `fetch` directly in a controller | A dedicated `assets/js/<feature>/api.js` module isn't practical — rare. Default: controllers call the module, module does `fetch` |
| New npm dependency | Stdlib + Stimulus + existing deps don't cover it, AND bundle-size delta ≤ 30 kB gzipped |
| Polyfill | Target browser actually needs it (check against Encore/browserslist config before adding) |

### Forbidden in committed code

- `console.log`, `console.debug`, `debugger`
- Inline `<script>` or `<style>` blocks in Twig
- Hardcoded absolute URLs that should be `{{ path(...) }}` or `{{ url(...) }}`
- Feature-scoped CSS leaking into global selectors (e.g. bare `div { ... }` at feature scope)

### Escape hatch

Same policy and recording format as `code-standards.md` §3.

---

## 11. Forward compatibility (not implemented yet)

These entries exist so contributors don't accidentally close future doors. Each becomes its own rule amendment when introduced — do not implement speculatively.

- **Authentication**: via Symfony Security bundle. Stimulus controllers default to session-based auth; switch to token headers only when the external headless API demands it.
- **API consumption**: when the project starts calling the future API Platform backend, create `assets/js/<feature>/api.js` as the single place that adds auth headers, handles errors, and shapes responses. Controllers stop calling `fetch` directly once an API module exists.
- **User-specific state** (favorites, saved filters): persist server-side via a Symfony controller + DTO; mirror into Stimulus values on page load. Domain state never lives in `localStorage`.
- **Cross-project integration** (this project ↔ the headless vehicle/expense tracker): mechanism undecided (SSO, embed, API-only). Keep each Stimulus feature self-contained until the mechanism is chosen — do not design for a specific integration shape today.
