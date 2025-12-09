# Task Pool (next steps)

Public repo; keep this list short (about 7) and up to date. Rules: no numbering (keeps churn low), prune completed items, and replace them with the next priority. Contributors: pick one task, keep PRs focused, and update the list as things land.

- String validators bundle
  - Add `hostname()`, `time()`, `base64()`, `hex()`, and `regex()` alias to `StringValidator` with tests and docs.

- Array length helpers
  - Implement `minItems()` / `maxItems()` for `ArrayValidator`, with coercion/null-handling parity and documentation.

- Universal enum/const
  - Add `enum()` and `const()` validators available on all types; cover mixed scalar inputs and document usage.

- Error metadata
  - Introduce structured error codes and enhanced error paths for nested schemas; keep backward compatibility for existing error shapes.

- Mutation testing pilot
  - Wire Infection (or similar) to the suite, add baseline config, and document how to run it locally.

- Property-based tests
  - Add a small property-based test set for key validators (string patterns, numeric constraints) to harden edge cases.

- Array contains helper
  - Add `contains()` to `ArrayValidator` (with item validator support), mirroring existing null/coercion semantics and documenting usage.
