# Quality Gates

Run these gates in order after every implementation task. All must pass before the task is considered done.

**On failure:** stop, fix the failure, re-run the failed gate and **all subsequent gates**. (Subsequent, not previous — the fix may have broken something downstream.)

---

## Gate sequence

1. **Tests** — `make phpunit` (all suites). Target a single suite: `make phpunit args="--testsuite Unit"`. Run this first; static analysis on broken code is misleading.

2. **Simplify** — invoke the **`simplify` skill** on changed files.

3. **Tests (re-run)** — simplify may change logic. Re-run `phpunit` to confirm nothing regressed. If gate 1 passed but this one fails, the fix lives in what simplify changed.

4. **PHPStan** — run `phpstan-analyse` via the **symfony-ai-mate MCP tool** (`phpstan-analyse`). Fix all reported errors.

5. **phpcs** — run via `make phpcs`. Fix all style violations.

5.5. **Frontend lint** — when the task touched any file under `assets/`, run `docker compose exec node npm run lint` (eslint + stylelint + `prettier --check`). Fix all reported violations (`npm run format` auto-fixes Prettier issues). Skip when no `assets/` files changed.

6. **Security review** — invoke the **`security-review` skill** when the task touches credentials, external calls, env vars, or auth logic. Skip otherwise.

6.5. **Delegation scope review** — if the task spawned any Agent or Skill calls, verify each prompt's content was bounded to the project root. `~/.claude/` paths are only acceptable if the user explicitly confirmed delegation before the call. Record PASS or FAIL with the offending prompt text if any. Skip entirely if no delegation occurred.

7. **Test coverage answer** — answer YES or NO: were tests added or updated for this task? Record in `quality_gate_outcomes`.
   - Expected answer follows the table in `testing.md`. YES is the default for any task type that appears there.
   - NO is legitimate only when `testing.md` says no tests are required (pure refactor, no logic change). Record the justification inline.

8. **Acceptance criteria verification** — for each acceptance criterion in the task spec, record one of:
   - **MET** — cite the observed evidence (file:line, command output, system response, screenshot path).
   - **FAILED** — cite the gap.
   - **BLOCKED** — name the external system that has not yet acted (CI pipeline, deploy job, user-side install, droplet command).

   Any **BLOCKED** or **FAILED** entry means the task verdict is **FAIL**. A FAIL verdict means the task stays `Status: in-progress` with a `Blocked on: <X>` line in the task file; the file is **not moved** to `completed/`, `task_outcomes_log` is **not appended**, and `task_current_spec` is **not cleared**.

   Phrases like *"PASS (with caveat)"*, *"documented procedure ready"*, *"awaiting install"*, *"pending verification"*, *"operator-verified after merge"* are not PASS — they are BLOCKED.
