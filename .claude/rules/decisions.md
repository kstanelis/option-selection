# Architectural Decisions (Open)

Design decisions that are not yet resolved in code. When a decision is made, remove the item from this file and reflect the choice in `stack.md`, `code-standards.md`, or relevant memory — whichever is authoritative for the concern.

## Open

- **Filterable output**: filtering happens **server-side** (querying DB/cache on each request) or **client-side** (precomputed JSON shipped with the static site). Drives the processing layer's output shape and cache strategy.
- **"Static" website**: "static" means **fully pre-rendered HTML** (crawl + dump on a build step) or **cached Symfony pages** served dynamically. Changes how ingestion triggers deploy / cache invalidation.

## Resolved (for reference)

The following are decided and captured elsewhere — listed here so the decision record is traceable:

- **SharePoint access** → credentials are env vars (see `code-standards.md` §2 Environment & secrets); the client is isolated behind `SharePointDownloaderInterface`; downloads must remain idempotent. Implemented.
- **Scheduling** → `symfony/scheduler` with a 10-minute cadence and a 2-hour post-download throttle. Messenger transport: `doctrine`. See memory `project_infra_decisions.md`.
- **Frontend stack** → Webpack Encore + Symfony UX Stimulus + Twig + plain CSS. No SPA framework, no AssetMapper, no Live Components as default. Full rules in `frontend.md`. Decision drivers: project intent (server-rendered, progressively enhanced), infra already provisioned (Encore + `node` container), forward-compatible with future API consumption from the headless API Platform project.
- **Per-command model tiers** → `/plan` and `/review-gates` on Opus; `/task` and `/notice` on Sonnet. Declared in each command's frontmatter (`.claude/commands/*.md`). Resolved 2026-04-24 by splitting the old monolithic `/task` into `/plan` + `/task`.
