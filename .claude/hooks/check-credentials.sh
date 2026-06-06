#!/usr/bin/env bash
# Scans a just-written PHP file for hardcoded credentials.
# Receives Claude Code hook JSON on stdin.

f=$(jq -r '.tool_input.file_path // .tool_input.pathInProject // ""')

# Normalize JetBrains MCP relative paths to an absolute path under the project root
[[ "$f" != /* && -n "$f" && -n "$CLAUDE_PROJECT_DIR" ]] && f="$CLAUDE_PROJECT_DIR/$f"

# Check PHP, env, YAML, JSON, and JS/TS files
case "$f" in
  *.php|*.env*|*.yaml|*.yml|*.json|*.js|*.ts) ;;
  *) exit 0 ;;
esac

# Skip vendor, node_modules, and var directories
[[ "$f" == */vendor/* || "$f" == */var/* || "$f" == */node_modules/* ]] && exit 0

# Grep for common credential patterns — matches both quoted and unquoted values
# Skip commented lines and env/variable references ($VAR, %VAR, {VAR})
matches=$(grep -nEi \
  "(password|secret|api_key|private_key|access_token|auth_token|client_secret)[[:space:]]*[:=>]+[[:space:]]*['\"]?[^$%{}\s][^$%{}\s\";,]*['\"]?" \
  "$f" 2>/dev/null \
  | grep -v '^\s*//' \
  | grep -v '^\s*#' \
  | grep -v '\$\|%\|{' \
  | head -3)

if [ -n "$matches" ]; then
  jq -cn --arg m "Possible hardcoded credential in $f:"$'\n'"$matches" \
    '{"systemMessage": $m}'
  exit 1
fi

exit 0
