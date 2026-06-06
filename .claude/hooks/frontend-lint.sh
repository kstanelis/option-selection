#!/usr/bin/env bash
# Runs eslint, stylelint, and prettier --check against a just-written JS/CSS file.
# Receives Claude Code hook JSON on stdin. Exits 1 on violations; exits 0 when clean.

COMPOSE="docker compose -f compose.yaml -f compose.dev.yaml --env-file .env.local"

# Extract file path from JSON
f=$(jq -r '.tool_input.file_path // .tool_input.pathInProject // ""')

# Normalize JetBrains relative paths to absolute
[[ "$f" != /* && -n "$f" && -n "$CLAUDE_PROJECT_DIR" ]] && f="$CLAUDE_PROJECT_DIR/$f"

# Only check JS and CSS files
[[ "$f" == *.js || "$f" == *.css ]] || exit 0

# Only allow assets/ scope
[[ "$f" == */assets/* ]] || exit 0

# Skip vendor and node_modules directories
[[ "$f" == */vendor/* || "$f" == */node_modules/* ]] && exit 0

# Check if node container is running (graceful skip if not)
if ! $COMPOSE ps node 2>/dev/null | grep -q "node"; then
  jq -cn --arg m "Skipping frontend lint (node container not running)" '{"systemMessage": $m}'
  exit 0
fi

# Translate to container path: /Users/.../project/pigiaukuras/ → /app/
container_path="/app/${f#$CLAUDE_PROJECT_DIR/}"

# Collect violations from applicable linters
output=""

# ESLint for .js files
if [[ "$f" == *.js ]]; then
  eslint_out=$($COMPOSE exec -T node npx eslint "$container_path" 2>&1)
  eslint_code=$?
  if [ $eslint_code -ne 0 ]; then
    output+="ESLint:
$eslint_out
"
  fi
fi

# Stylelint for .css files
if [[ "$f" == *.css ]]; then
  stylelint_out=$($COMPOSE exec -T node npx stylelint "$container_path" 2>&1)
  stylelint_code=$?
  if [ $stylelint_code -ne 0 ]; then
    output+="Stylelint:
$stylelint_out
"
  fi
fi

# Prettier --check for both .js and .css
prettier_out=$($COMPOSE exec -T node npx prettier --check "$container_path" 2>&1)
prettier_code=$?
if [ $prettier_code -ne 0 ]; then
  output+="Prettier:
$prettier_out
"
fi

# Emit if any violations
if [[ -n "$output" ]]; then
  jq -cn --arg m "Frontend lint violations in $f:
$output" '{"systemMessage": $m}'
  exit 1
fi

exit 0