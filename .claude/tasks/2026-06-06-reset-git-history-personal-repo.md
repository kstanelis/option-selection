---
Status: in-progress
Created: 2026-06-06
---

## Goal
Replace the project's git history with a single `Initial commit` authored by Karolis and push to `git@github.com:kstanelis/option-selection.git` as `main`.

## In scope
- Create an orphan branch holding the current working tree as one initial commit.
- Delete local `main` and `foundation-api-platform-scaffold` branches.
- Rename the new orphan branch to `main`.
- Add `origin` remote pointing at `git@github.com:kstanelis/option-selection.git`.
- Push `main` to `origin` (plain push; stop and ask if the remote is non-empty).

## Out of scope
- Any change to source files, dependencies, or app configuration.
- Force-pushing without explicit user confirmation if the remote is non-empty.
- Preserving the 4 prior Karolis commits as separate commits.
- Branch protection rules, CI configuration, or GitHub repo metadata.
- Edits to `.claude/` rules or memory beyond the standard `/task` Phase 4 housekeeping.

## Acceptance criteria
1. `git log --oneline` shows exactly **one** commit on `main` with subject `Initial commit`, authored by `Karolis Stanelis <k.stanelis@gmail.com>`.
2. `git remote -v` shows `origin = git@github.com:kstanelis/option-selection.git` for both fetch and push.
3. `git branch -a` shows local `main` and `remotes/origin/main` only — no `foundation-api-platform-scaffold` or other locals.
4. Push to `origin/main` succeeded (verified via `gh repo view kstanelis/option-selection` or user-confirmed browser check).
5. Working tree remains clean; no files differ from the pre-rewrite snapshot.

## Plan
- [ ] Step 1 — Preflight: `git status` (clean), `git remote -v` (no origin), record current branch tips for reflog recovery.
- [ ] Step 2 — Create orphan branch: `git checkout --orphan new-main`, then `git add -A` so the index reflects the current working tree.
- [ ] Step 3 — Pre-commit safety check: `git diff --cached --stat` to confirm nothing sensitive is staged (no `.env.local`, `vendor/`, `var/cache/`, `var/log/`, `node_modules/`, `public/build/`). Halt if anything unexpected appears.
- [ ] Step 4 — Create the initial commit: `git commit -m "Initial commit"`. No `Co-Authored-By` trailer.
- [ ] Step 5 — Replace `main`: `git branch -D main`, `git branch -D foundation-api-platform-scaffold`, then `git branch -m main`.
- [ ] Step 6 — Configure remote: `git remote add origin git@github.com:kstanelis/option-selection.git`.
- [ ] Step 7 — Push: `git push -u origin main`. If it fails because the remote is non-empty, STOP and ask the user before any `--force` push.
- [ ] Step 8 — Verify acceptance criteria: `git log --oneline`, `git branch -a`, `git remote -v`, and (if `gh` available) `gh repo view kstanelis/option-selection --json url,defaultBranchRef`.
