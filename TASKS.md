# Task Pool (next steps)

Public repo; keep this list short (about 7) and up to date. No numbering. Prune completed items and replace with the next priority. Contributors: pick one task, keep PRs focused, update the list as things land.

- Structured error codes

  Add programmatic error codes to validation errors (e.g., STRING_TOO_SHORT, INVALID_EMAIL) for better error handling and i18n support; keep backward compatibility.

- Mutation testing pilot

  Wire Infection (or similar) to the test suite, add baseline config, and document how to run it locally for enhanced test quality verification.

- Property-based tests

  Add a small property-based test set for key validators (string patterns, numeric constraints) to harden edge cases and catch regressions.

- Deprecation cleanup

  Remove or finalize deprecated methods (addValidation, allOf, anyOf, not) before v1.0; update call sites and docs.

- forbidKeys

  Explicit key deny-listing on AssociativeValidator/ObjectValidator: forbidKeys(array $keys, ?string $message = null).

- Performance benchmarking

  Establish baseline for hot paths (validate, tryValidate, schema validation); document how to run and interpret results.

- Docs refresh

  Update examples, ensure API reference matches current surface, add migration notes where relevant.
