---
description: Plan a task — clarify, confirm spec, write task file. Judgment-heavy; runs on Opus.
model: claude-opus-4-7
---

# /plan — Task planning

The user has described a task (or revision): **$ARGUMENTS**

Your only job in this command is planning. **Do not implement anything.** Implementation happens in the separate `/task` command on a mid-tier model.

---

## Plan mode behavior

When this command runs, it calls `EnterPlanMode` to enter planning mode. In this mode:
- File writes (including `Write` and `Edit`) are blocked except for the plan file itself.
- The command focuses on exploration and spec clarification.
- `ExitPlanMode` is called only after the user explicitly confirms the spec — this is the approval gate that unblocks file writes for Phase 3 (implementation in `/task`).

---

## Step 0 — Decide: new plan or revision?

1. Call `EnterPlanMode` to enter planning mode for structured exploration and spec iteration.

2. Scan `.claude/tasks/*.md` (root, not `completed/`).
   - If a file has `Status: in-progress` in its header → this is a **revision**. Read that file (spec + Plan checklist + any notes). Treat `$ARGUMENTS` as "here is what went wrong / what needs to change". Jump to Step 3 (revise), skipping Step 1 only if the Goal itself is unchanged.
   - Otherwise → this is a **new plan**. Continue to Step 1.

---

## Step 1 — Clarify and explore

1. Use `sequentialthinking` MCP to analyze the request and break down scope.
2. Ask up to 5 clarifying questions focused on: scope boundaries, acceptance criteria, what must NOT change, edge cases. Do not ask about things already clear from the description. Collect answers from the user.
3. Spawn an `Explore` subagent with the task description and the answers from step 2 (clarifying questions). Instruct it to use `codebase-memory-mcp` to analyze the codebase and understand the impact and context of the requested changes. Other available MCPs (`context7`, `gitlab`, etc.) should be used at the agent's discretion if the task requires them.

---

## Step 2 — Confirm spec

Present in this exact structure:

```
Goal: <one sentence>
In scope:
  - ...
Out of scope:
  - ...
Acceptance criteria:
  1. ...
```

Iterate on the spec with the user — refine, adjust, answer questions. Continue until the user explicitly confirms the spec (with "confirmed", "yes", "proceed", or similar). Then:

1. Save the confirmed spec to memory as project memory `task_current_spec` (overwrite if exists).
2. Call `ExitPlanMode` to exit plan mode and unblock file writes for Step 3.

---

## Step 3 — Write (or revise) the task file

Path: `.claude/tasks/<YYYY-MM-DD>-<descriptive-kebab-case-slug>.md`. The `<YYYY-MM-DD>` prefix is today's date; slug reflects the task or issue — **not** the git branch name.

**Header (required):**

```
---
Status: planned
Created: <YYYY-MM-DD>
---
```

Use `Status: planned` for a new plan. A file is flipped to `Status: in-progress` by `/task` when implementation starts — not here.

**Body:**

```
## Goal
<one sentence>

## In scope
- ...

## Out of scope
- ...

## Acceptance criteria
1. ...

## Plan
- [ ] Step 1 — ...
- [ ] Step 2 — ...
- [ ] Step 3 — ...
```

**Memory updates are not deliverables.** Do NOT list memory file updates (`~/.claude/.../memory/*.md`) in `## In scope` or `## Plan`. Memory housekeeping is a Phase 4 side-effect in `/task`, not a numbered implementation step.

Status markers: `[ ]` not started, `[~]` in progress, `[x]` done. `/task` maintains these during implementation.

**Revision mode (Step 0 found an in-progress file):**

- Keep every `[x]` step as-is — do not rewrite history.
- For the step the user flagged as wrong, add a `> Revision <YYYY-MM-DD>: <reason>` line beneath it and, if it had been marked `[x]` or `[~]`, flip it back to `[ ]` or split it into corrected sub-steps.
- Append new steps to the end for the new direction.
- Leave `Status: in-progress` untouched — `/task` resumes from the first non-`[x]` step.
- Update `task_current_spec` only if Goal / In scope / Out of scope / Acceptance criteria actually changed.

---

## Step 4 — Hand off

State explicitly: *"Plan saved to `.claude/tasks/<YYYY-MM-DD>-<slug>.md`. Run `/task` to implement."*

Do not run `/task` yourself, do not edit any source files, do not touch `src/`, `tests/`, `assets/`, or migrations. Planning is done.
