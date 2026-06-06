---
description: Report an agent behavior issue — classify, propose fix, log. Runs on Sonnet.
model: claude-haiku-4-5-20251001
---

# /notice — Report an agent behavior issue

The user has reported: **$ARGUMENTS**

This command **logs only**. Fix wording and application are owned by `/review-gates` — never by `/notice`.

Follow these steps in order.

---

## Step 1 — Capture

Identify from the report:
- **Observed**: what the agent did
- **Expected**: what the agent should have done
- **Trigger**: in what situation this occurs

If the report is ambiguous, ask one clarifying question before proceeding.

---

## Step 2 — Classify

Classify into one or more categories:

- **Rule gap** — CLAUDE.md is missing a rule that would have prevented this
- **Memory gap** — memory is missing context that would have informed better behavior
- **Quality gate gap** — a quality check would have caught this if it existed
- **Workflow gap** — the `/task` workflow phases are missing a step

---

## Step 3 — Identify fix area (one sentence only)

State the most likely fix area in one sentence (e.g. `Rule gap — CLAUDE.md Meta-directives needs a third-party-fact-check directive`).

**Do not draft the fix wording. Do not present any proposal for approval. Do not apply anything.** All fix wording, confirmation, and application belong to `/review-gates`.

---

## Step 4 — Log Notice

Append to memory file `notices_log`:
- Date: today's date
- Observed behavior
- Expected behavior
- Classification
- Fix area (the one-sentence Step 3 output)
- Fix applied: `pending — queued for /review-gates`

Output to the user: a one-sentence confirmation that the notice was logged and queued. **Do not** ask whether to apply, propose wording, or open an implementation discussion.