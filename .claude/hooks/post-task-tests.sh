#!/bin/bash
# Runs automatically after Claude stops — only proceeds if a task was just completed.
# Exit 2 triggers asyncRewake so Claude is re-engaged on test failure.

PROJECT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$PROJECT_DIR"

if ! find .claude/tasks/completed -name "*.md" -mmin -2 2>/dev/null | grep -q .; then
  exit 0
fi

echo "=== Post-task tests ==="
FAILED=0

echo "--- PHP tests ---"
make phpunit || FAILED=1

echo "--- Playwright tests ---"
if docker compose -f compose.yaml -f compose.dev.yaml --env-file .env.local ps --status running playwright 2>/dev/null | grep -q playwright; then
  docker compose -f compose.yaml -f compose.dev.yaml --env-file .env.local exec -T playwright npx playwright test || FAILED=1
else
  echo "Playwright container not running — skipping E2E tests (run manually via Docker)"
fi

if [ "$FAILED" -eq 1 ]; then
  echo "One or more test suites FAILED."
  exit 2
fi

echo "All tests passed."