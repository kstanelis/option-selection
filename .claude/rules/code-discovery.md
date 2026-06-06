# Code Discovery Protocol

When exploring the codebase to locate code, understand architecture, trace dependencies, or perform impact analysis, use this tool hierarchy:

## Priority 1: codebase-memory-mcp (for code queries)

Always try codebase-memory-mcp tools first for code exploration. The knowledge graph indexes functions, classes, routes, entities, and their relationships.

- **`search_graph(name_pattern/label/qn_pattern)`** — find functions, classes, routes by name or type. Returns definitions + structural metadata.
- **`trace_path(function_name, mode=calls|data_flow|cross_service)`** — trace call chains, data flow, and cross-service dependencies (HTTP/async calls).
- **`get_code_snippet(qualified_name)`** — read source code for a specific function/class. Use the qualified_name from `search_graph` results. **Do not use Read/cat/head** when a code snippet is available via the graph.
- **`query_graph(query)`** — execute Cypher queries for complex patterns across multiple hops or aggregations (e.g., "find all functions that read X and write Y").
- **`get_architecture(aspects)`** — get high-level packages, services, dependencies, and project structure.
- **`search_code(pattern)`** — graph-augmented text search; deduplicates grep results into containing functions and ranks by structural importance.

If the graph is not indexed yet, run `index_repository` first. The initial index takes 1-2 minutes for a medium codebase.

## Priority 2: Grep / Glob / Read (for text content)

Fall back to text-search tools **only** when codebase-memory-mcp does not apply:

- Config values (`.env`, YAML, JSON, PHP constants)
- Documentation (README, CHANGELOG, comments)
- Non-code files (test fixtures, assets, data files)
- Text patterns in comments or docstrings where graph search is not suitable

Examples:
- Looking for a constant value → `grep -r "THROTTLE_DURATION"`
- Finding a config option → read `config/services.yaml`
- Checking a Twig filename → `find . -name "*.twig"`

## Decision tree

| Question | Answer | Use |
|---|---|---|
| "Where is function `frob` defined?" | Code entity | `search_graph(name_pattern="frob")` |
| "What calls `frob`?" | Call relationships | `trace_path("frob", mode="calls", direction="inbound")` |
| "What is the data flow into `frob`'s parameter?" | Data propagation | `trace_path("frob_param", mode="data_flow")` |
| "What's the architecture of this project?" | Structure overview | `get_architecture()` |
| "Find all DELETE routes" | Routes by type | `search_graph(label="Route", query="DELETE")` |
| "Where is `CACHE_TTL` set?" | Config constant | `grep -r "CACHE_TTL"` or read the config file |
| "What does line 47 of `FooService.php` do?" | Specific source snippet | `get_code_snippet` (from a `search_graph` result) |

## When not to use Grep

Avoid using Grep/Read as your primary code-discovery tool for:
- Locating function/class definitions
- Tracing call chains or imports
- Understanding which services depend on others
- Finding all usages of an entity
- Analyzing data flow through the app

These are slow and error-prone compared to the graph. Use the graph first; grep becomes the fallback.
