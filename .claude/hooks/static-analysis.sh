#!/usr/bin/env bash
# Runs php -l, phpstan, and phpcs against a just-written PHP file.
# Receives Claude Code hook JSON on stdin. Always exits 0 (advisory).

COMPOSE="docker compose -f compose.yaml -f compose.dev.yaml --env-file .env.local"

# Extract file path from JSON
f=$(jq -r '.tool_input.file_path // .tool_input.pathInProject // ""')

# Normalize JetBrains relative paths to absolute
[[ "$f" != /* && -n "$f" && -n "$CLAUDE_PROJECT_DIR" ]] && f="$CLAUDE_PROJECT_DIR/$f"

# Only check PHP files
[[ "$f" == *.php ]] || exit 0

# Skip vendor and var directories
[[ "$f" == */vendor/* || "$f" == */var/* ]] && exit 0

# Only allow src/ and tests/ scope
[[ "$f" == *"/src/"* || "$f" == *"/tests/"* ]] || exit 0

# Skip tests/bootstrap.php
[[ "$f" == */tests/bootstrap.php ]] && exit 0

# Translate to container path: /Users/.../project/pigiaukuras/ → /app/
container_path="/app/${f#$CLAUDE_PROJECT_DIR/}"

# Syntax check (short-circuit on error)
lint_out=$($COMPOSE exec -T php php -l "$container_path" 2>&1)
if [ $? -ne 0 ]; then
  jq -cn --arg m "Parse error in $f:
$lint_out" '{"systemMessage": $m}'
  exit 1
fi

# Run analyzers and collect output (only emit on non-zero exit code)
output=""

phpstan_out=$($COMPOSE exec -T php vendor/bin/phpstan analyse "$container_path" 2>&1)
phpstan_code=$?
if [ $phpstan_code -ne 0 ]; then
  output+="PHPStan:
$phpstan_out
"
fi

phpcs_out=$($COMPOSE exec -T php vendor/bin/phpcs "$container_path" 2>&1)
phpcs_code=$?
if [ $phpcs_code -ne 0 ]; then
  output+="PHPCS:
$phpcs_out
"
fi

# Emit if any findings
if [[ -n "$output" ]]; then
  jq -cn --arg m "Static analysis findings in $f:
$output" '{"systemMessage": $m}'
  exit 1
fi

exit 0