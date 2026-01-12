# Task Pool (next steps)

Public repo; keep this list short (about 7) and up to date. Rules: no numbering (keeps churn low), prune completed items, and replace them with the next priority. Contributors: pick one task, keep PRs focused, and update the list as things land.

- Universal enum/const validators
  - Add `enum()` and `const()` methods available on all validator types; handle mixed scalar inputs and document usage patterns.

- Structured error codes
  - Add programmatic error codes to validation errors (e.g., 'STRING_TOO_SHORT', 'INVALID_EMAIL') for better error handling and i18n support; keep backward compatibility.

- `in()` alias for `oneOf()`
  - Add `in()` method as an alias for `oneOf()` for more intuitive API (`->in(['active', 'inactive'])` reads better than `->oneOf()`).

- Mutation testing pilot
  - Wire Infection (or similar) to the test suite, add baseline config, and document how to run it locally for enhanced test quality verification.

- Property-based tests
  - Add a small property-based test set for key validators (string patterns, numeric constraints) to harden edge cases and catch regressions.

- `uniqueField()` array validation method
  - Add `uniqueField(string $fieldName, ?string $message = null)` to ArrayValidator for validating uniqueness of nested fields across array items; convenience wrapper around `satisfies()` that automatically handles nested error structure for field-level error paths (e.g., `symlinks.2.destination`); common use case that deserves convenience method similar to Laravel's `distinct` rule.
