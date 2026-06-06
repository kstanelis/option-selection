#!/bin/bash
# SessionStart hook: full reindex of the active project's codebase graph.
# Runs codebase-memory-mcp `index_repository` in the background and detaches.
# No-op if not invoked inside a project (CLAUDE_PROJECT_DIR unset).
set -u
LOG_DIR="$HOME/.claude/logs"
mkdir -p "$LOG_DIR"
LOG_FILE="$LOG_DIR/cbm-reindex.log"
BIN="$HOME/.local/bin/codebase-memory-mcp"

if [ -z "${CLAUDE_PROJECT_DIR:-}" ] || [ ! -x "$BIN" ]; then
  exit 0
fi

nohup "$BIN" cli index_repository "{\"repo_path\":\"$CLAUDE_PROJECT_DIR\"}" \
  >> "$LOG_FILE" 2>&1 < /dev/null &
disown 2>/dev/null || true
exit 0
