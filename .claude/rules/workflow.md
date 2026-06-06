# Agent Workflow

Four slash commands orchestrate all work. Use them — do not bypass the phases. Each command declares its own model tier in its frontmatter; do not try to change models inside a command.

| Command | Model | Purpose |
|---|---|---|
| `/plan <description>` | claude-opus-4-7 | Clarify scope, confirm spec, write the task file. Produces a plan — **does not** touch source code. |
| `/task [<slug>]` | claude-haiku-4-5-20251001 | Implement the planned task, run quality gates, validate goal, log outcome, close the task file. |
| `/notice <observation>` | claude-haiku-4-5-20251001 | Report an agent behavior issue. Classifies, proposes fix, applies after confirmation, logs to `notices_log`. |
| `/review-gates` | claude-opus-4-7 | Periodic quality-gate improvement review. Reads `notices_log`, `quality_gate_outcomes`, `task_outcomes_log`; proposes gate changes; applies after confirmation; logs to `gate_review_log`. |

## Everything goes through /plan → /task

There is no "plain message" escape hatch. Even a one-line config tweak with no behavior impact goes:

1. `/plan` writes `.claude/tasks/<YYYY-MM-DD>-<slug>.md` with `Status: planned`.
2. `/task` picks it up, implements, runs gates, closes it.

Clarifying questions that don't change anything (pure read/explain) can still be answered in a plain message — the rule is about **edits**, not conversation.

## Task file

Every `/plan` invocation produces a markdown file at `.claude/tasks/<YYYY-MM-DD>-<slug>.md`, where the date prefix is the creation date (set by `/plan`) and slug is descriptive kebab-case reflecting the task or issue — **not** the git branch name (e.g. `2026-05-09-refactor-fuel-price-normalizer.md`, `2026-05-09-fix-sharepoint-throttle.md`).

**Header format (YAML frontmatter):**

```
---
Status: planned | in-progress | done
Created: YYYY-MM-DD
Completed: YYYY-MM-DD (added by /task when closing; omitted before closure)
---
```

**Lifecycle:**

| Stage | Who writes it | Status value | File location |
|---|---|---|---|
| Just planned | `/plan` | `planned` | `.claude/tasks/<YYYY-MM-DD>-<slug>.md` |
| Being implemented | `/task` flips on start | `in-progress` | `.claude/tasks/<YYYY-MM-DD>-<slug>.md` |
| Closed | `/task` adds `Completed:`, flips status, moves | `done` | `.claude/tasks/completed/<YYYY-MM-DD>-<slug>.md` |

**Plan checklist (inside the body):**

- `[ ]` not started
- `[~]` in progress
- `[x]` done

`/task` maintains these markers during Phase 1 — flip `[ ]` → `[~]` when starting a step, `[~]` → `[x]` when finishing it. Add tactical sub-steps if they surface; do not add scope.

## /task auto-pick

`/task` does not ask the user to name the task file. It picks:

1. If `$ARGUMENTS` matches a file (either as a bare slug like `update-rules`, or as a full dated filename like `2026-05-09-update-rules.md`) → use it.
2. Else if exactly one file in `.claude/tasks/` (root, not `completed/`) has `Status: in-progress` → use it.
3. Else if exactly one file in `.claude/tasks/` has `Status: planned` → flip to `in-progress`, use it.
4. Else (zero candidates, or multiple `planned` with no `in-progress`) → list candidates, ask the user to pick. Never guess.

## Re-planning mid-implementation

If scope turns out wrong while `/task` is running:

1. Stop `/task`. Do **not** edit the plan file from inside `/task`.
2. Run `/plan <what went wrong / what needs to change>`.
3. `/plan` detects the in-progress file, keeps every `[x]` step intact, annotates the flagged step with a `> Revision <date>: <reason>` line, and appends new steps for the corrected direction. `Status` stays `in-progress`.
4. Re-run `/task` — it resumes from the first non-`[x]` step.

This separation is deliberate: planning judgment stays on claude-opus-4-7; implementation stays on claude-haiku-4-5-20251001.

## Rules

- **`/plan` must investigate before proposing**: For diagnostic requests ("why is X failing", "check this job"), complete all necessary fact-finding — pipeline job logs, git diffs, config file state, prior merges — before drafting a spec. Do not propose a solution while facts are still assumed. Surface any remaining uncertainty by asking; do not hide it in the plan.
- **`/plan` must keep scope minimal**: Do not fold in "what if" or edge-case handling unless the user explicitly asks. If an edge case is worth raising, ask about it *after* presenting the minimal proposal.
- **Memory updates are not spec deliverables**: Do not list memory file updates (`~/.claude/.../memory/*.md`) in `## In scope` or `## Plan` steps. Memory housekeeping is a `/task` Phase 4 side-effect, not a numbered implementation step.
- The confirmed spec from `/plan` Step 2 is the single source of truth. `/task` implementation must not exceed it.
- Goal validation (`/task` Phase 3) must produce an explicit PASS before the task is closed. Subagent required when the task touched >1 file OR any gate failed on first run; otherwise inline validation is acceptable.
- Every quality gate outcome must be logged to `quality_gate_outcomes` — this feeds `/review-gates`.
- `task_current_spec` memory is transient: set by `/plan` Step 2, cleared by `/task` Phase 4. Never leave a closed task's spec in memory.
- Run `/review-gates` after 3+ notices or 5+ completed tasks.

## Git discipline

- **Branches**: one task per branch. Name: short-kebab-case (e.g. `adjust-ai-rules`, `refactor-entities`). Never work directly on `main`.
- **Commits**: small and focused. Subject in imperative mood, ≤72 chars, no trailing period. Body (optional) explains *why*, not *what*.
- **When to commit**: at least once per Phase 1 milestone of `/task`, and always after Phase 4. Do not amend or force-push a pushed branch without asking.
- **Never commit**: `.env.local`, secrets, `var/cache`, `var/log`, generated assets, or files matching the credential-scan hook.
- **Merges**: GitLab merge requests only — do not merge locally into `main`. Target branch is always `main`.
- **Before composing any commit message**, scan `MEMORY.md` for entries whose description matches the keywords `commit`, `message`, `author`, `signature`, `co-author`, or `co-authored`. Apply every matching entry. The default trailer set is **empty** — never add `Co-Authored-By` lines unless an explicit user memory or in-session instruction authorizes it.
