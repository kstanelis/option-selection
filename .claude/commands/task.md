---
description: Implement a planned task — run quality gates, validate, log, close. Mechanical; runs on Sonnet.
model: claude-haiku-4-5-20251001
---

# /task — Implement a planned task

Optional argument (slug override): **$ARGUMENTS**

This command **implements** a task that has already been planned via `/plan`. If no plan exists, stop and tell the user to run `/plan` first.

---

## Phase 0 — Pick the task file

Scan `.claude/tasks/*.md` (root only, never `completed/`).

1. If `$ARGUMENTS` matches a file (either as a bare slug like `update-rules`, or as a full dated filename like `2026-05-09-update-rules.md`) → use it.
2. Else if exactly one file has `Status: in-progress` → use it (resuming an interrupted run or a revision).
3. Else if exactly one file has `Status: planned` → flip its header to `Status: in-progress`, use it.
4. Else (zero candidates, or multiple planned with none in-progress) → stop, list candidates, ask the user which to run. Do not guess.

Read the confirmed spec and Plan checklist from the chosen file. This spec is the single source of truth; do not exceed it.

If scope looks wrong mid-implementation: stop, tell the user, suggest `/plan` to revise. Do not edit the plan from here.

---

## Phase 1 — Implement

Follow CLAUDE.md and every rule in `.claude/rules/` (stack, code-standards, frontend, testing, quality-gates, workflow).

- Update the Plan checklist as work progresses: `[ ]` → `[~]` on start, `[~]` → `[x]` on finish.
- Implement only what the spec lists. No extra error handling, no extra abstractions, no unrelated refactoring.

---

## Phase 2 — Quality gates

Run all gates in `.claude/rules/quality-gates.md`, in the order specified there. That file is the single source of truth — do not restate the gates here.

After the sequence passes, append outcomes to memory file `quality_gate_outcomes` (what each gate caught, what passed clean).

---

## Phase 3 — Goal validation

- **Subagent required** when the task touched >1 file OR any gate failed on its first run.
- **Inline validation** acceptable only when the task touched ≤1 file AND all gates passed first try.

Subagent instruction (when used):
- The confirmed spec from the task file
- A summary of what was actually implemented (files changed, logic added)
- *"Check whether the implementation matches the spec exactly. Flag any scope creep, missing acceptance criteria, or deviations. Return a PASS or FAIL verdict with specific findings."*

Inline: walk through each acceptance criterion against the diff and state PASS or FAIL explicitly.

On FAIL → return to Phase 1, fix deviations, re-run Phase 2, re-run Phase 3. If the FAIL is a spec problem (not an implementation one), stop and tell the user to run `/plan` to revise.

Each acceptance criterion in the spec must be recorded as MET / FAILED / BLOCKED per `quality-gates.md` gate 8. Any BLOCKED or FAILED entry blocks closure: the task remains `Status: in-progress` with a `Blocked on:` line; do not move the file, do not append to `task_outcomes_log`, do not clear `task_current_spec`. Resume only when the blocker resolves (re-run CI, user completes external step, etc.) or when the user explicitly instructs a different action.

---

## Phase 4 — Log, close, clean up

1. Append to memory file `task_outcomes_log`:
   - Date
   - Task goal (one sentence)
   - Quality issues found and fixed (or "none")
   - Goal validation verdict and findings
   - Any KISS/layer rule exceptions and their justification

2. Close the task file:
   - Set header to `Status: done` and add `Completed: <YYYY-MM-DD>` (today's date).
   - Confirm every Plan item is `[x]` (or explicitly noted as dropped, with reason).
   - Move the file from `.claude/tasks/<YYYY-MM-DD>-<slug>.md` to `.claude/tasks/completed/<YYYY-MM-DD>-<slug>.md`. Create `.claude/tasks/completed/` if it does not exist.

3. **Delete the `task_current_spec` memory file** — transient, must not survive task closure.

4. **Check `/review-gates` trigger** — count completed task entries in `task_outcomes_log`. If the count is a multiple of 5 (5, 10, 15, …), or if `notices_log` has 3+ entries logged since the last `gate_review_log` entry date, tell the user: *"Trigger met — run `/review-gates` before starting the next task."* Do not invoke `/review-gates` directly; the user controls model-tier transitions.

5. Consider whether a reusable, non-derivable fact was learned. If yes, write or update a memory entry per the auto-memory guide. If no, write nothing.
