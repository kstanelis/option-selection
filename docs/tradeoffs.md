# Design Tradeoffs: Variant A vs Variant B

This document discusses the two solution approaches for filtering parameter options and explains why **Variant A (Constraint List)** was chosen for implementation.

## Problem Statement

Given:
- Parameter 1 options: A, B, C
- Parameter 2 options: X, Y, Z
- Forbidden combination: (A, Y) and (C, Z)

When a user selects parameter1=A, parameter2 should only show [X, Z] (excluding Y).
When both are selected, we need to validate that the combination is valid.

## Variant A: Constraint List

**Approach**: Maintain an explicit list of forbidden combinations.

```php
$forbidden = [
    ['parameter1' => 'A', 'parameter2' => 'Y'],
    ['parameter1' => 'C', 'parameter2' => 'Z'],
];
```

**Logic**:
1. Load the constraint list (from config, database, or cache)
2. For each available option, check if combining it with the current selection violates a constraint
3. Filter out violating options

**Pros**:
- Simple to understand and audit
- Easy to debug: read the list, see exactly what's forbidden
- Fast queries if stored in a database with proper indexing
- Scales well for N parameters (just add more rows)
- Changes are versioned with git (if in config)
- Clear ownership: the business owns the rules list

**Cons**:
- Requires maintaining the list separately from the data
- If rules are complex (e.g., "A+Y forbidden only on Tuesdays"), the list grows quickly
- Need to synchronize between data model and rule model
- Prone to human error when managing the list manually

## Variant B: Valid Combination Table

**Approach**: Maintain an explicit table of all valid combinations (SKUs or tuples).

```php
$valid = [
    ['parameter1' => 'A', 'parameter2' => 'X'],
    ['parameter1' => 'A', 'parameter2' => 'Z'],
    ['parameter1' => 'B', 'parameter2' => 'X'],
    ['parameter1' => 'B', 'parameter2' => 'Y'],
    ['parameter1' => 'B', 'parameter2' => 'Z'],
    ['parameter1' => 'C', 'parameter2' => 'X'],
    ['parameter1' => 'C', 'parameter2' => 'Y'],
];
```

**Logic**:
1. Load the valid combinations (from SKU catalog or database)
2. For each parameter value, find all valid combinations where that parameter has that value
3. Return the set of compatible values for the other parameter

**Pros**:
- Directly represents the business reality: "these are the SKUs we sell"
- Single source of truth: no need to maintain both a list of products AND a list of forbidden combinations
- Easy to verify: just check if a combination is in the table
- Audit trail: see exactly which combinations are available and when they were added
- Works well for product catalogs where combinations come from real inventory

**Cons**:
- Table grows exponentially with number of parameters (N parameters → all combinations in O(n^k) space)
- For 3+ parameters, becomes unwieldy (e.g., 5×5×5 = 125 rows for 5 options each)
- Harder to understand at a glance what the rules are
- Changes to rules require data mutations, not just config updates
- More expensive to modify rules (database transaction instead of editing a config file)

## Decision: Variant A

**Chosen**: Variant A (Constraint List)

**Rationale**:
1. **Simplicity**: For 2-parameter systems (the current scope), Variant A is clearer
2. **Scalability to N parameters**: Variant A scales linearly; Variant B scales exponentially
3. **Maintenance**: Business rules are easier to version-control and review as constraints
4. **Future-proofing**: If the assessment later adds 3+ parameters, Variant A remains practical
5. **Auditability**: A list of "forbidden" rules is easier to explain to stakeholders than a whitelist of thousands of valid combinations

## Future: N Parameters

When the project expands to handle 3+ parameters:

- **Variant A**: Add constraint rows for each new parameter. Still simple.
  ```php
  ['parameter1' => 'A', 'parameter2' => 'Y', 'parameter3' => null], // null = any value
  ['parameter1' => 'A', 'parameter2' => 'Y', 'parameter3' => '1'], // specific combo
  ```
  This approach is extensible.

- **Variant B**: The valid combinations table becomes impractical.
  ```php
  // Hundreds of rows for a 5×5×5×5 space; unmaintainable
  ```

## Implementation Notes

### Variant A (This Task)

```php
interface ParameterOptionResolverInterface {
    public function availableOptions(array $selection): array;
}

class ConstraintListResolver implements ParameterOptionResolverInterface {
    public function availableOptions(array $selection): array {
        // Load constraints
        // Filter options by constraints
        // Return available options
    }
}
```

### Variant B (Not Chosen)

```php
class ValidCombinationTableResolver implements ParameterOptionResolverInterface {
    public function availableOptions(array $selection): array {
        // Load valid combinations table
        // Filter by current selection
        // Return compatible options
    }
}
```

Both implement the same interface, so the API remains identical. The variant branches will swap the resolver implementation without touching the endpoint logic.

## Summary

| Aspect | Variant A | Variant B |
|--------|-----------|-----------|
| **Simplicity** | ✓ | ✓ |
| **Scales to N parameters** | ✓ | ✗ |
| **Auditable** | ✓ | ~ |
| **Data-driven** | ~ | ✓ |
| **Chosen** | **YES** | No |
