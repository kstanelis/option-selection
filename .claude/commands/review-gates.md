---
description: Quality gate improvement review — synthesize patterns across logs, propose rule changes. Runs on Opus.
model: claude-opus-4-7
---

# /review-gates — Quality gate improvement review

Perform a systematic review of quality gate effectiveness. Follow all steps.

---

## Step 0 — Enter plan mode

Call `EnterPlanMode` immediately on invocation. Steps 1–3 (read history, analyze patterns, propose improvements) all run inside plan mode. Build the proposal list incrementally into the plan file. Do not modify any rule or command file from inside plan mode.

---

## Step 1 — Read History

Read from memory:
- `notices_log` — all reported agent behavior issues
- `quality_gate_outcomes` — what each gate caught or missed across recent tasks
- `task_outcomes_log` — task completion records including deviations

If any file does not exist yet, note it and continue with available data.

---

## Step 2 — Analyze Patterns

Identify:
1. Issues appearing in `notices_log` more than once — gates are not preventing recurrence
2. Issues found late in the workflow that could have been caught earlier
3. Checks that have never caught anything across recent tasks — potentially redundant or misaligned
4. Categories of problems with no corresponding gate

---

## Step 3 — Propose Improvements

For each pattern, produce one concrete proposal:

```
Pattern: <description of the recurring problem>
Proposed fix: <exact change — new check command, reorder, removal, or new CLAUDE.md rule>
Expected impact: <what this prevents>
```

Number each proposal. Present all before asking for confirmation.

---

## Step 4 — Confirm and Apply

Call `ExitPlanMode` with the populated plan file to surface the proposal list to the user. The user approves proposals individually or all at once outside plan mode.

After the user confirms (post-ExitPlanMode):
1. Update `.claude/rules/quality-gates.md` with approved additions/changes (that file is the single source of truth for the gate sequence)
2. Update `.claude/commands/task.md`, `.claude/commands/plan.md`, `.claude/commands/notice.md`, or `.claude/commands/review-gates.md` only if a change lands inside a phase those commands own
3. Update `CLAUDE.md` Meta-directives or `.claude/rules/workflow.md` if the approved fix belongs there
4. Update relevant memory entries

---

## Step 5 — Log Review

Append to memory file `gate_review_log`:
- Date
- Patterns identified
- Proposals made
- Proposals approved and applied