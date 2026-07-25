# Lemmon Validator — Roadmap

Single source of truth for planned and considered work. Completed work lives in `CHANGELOG.md`.

## Philosophy

- **The `pipe()`/`transform()` model with coercion is the core value to protect.** It is the library's defining feature; weigh every change first against whether it keeps that flow clean and pleasant to use, and never trade its ergonomics away for other goals.
- **Extensibility over reinvention** — integrate external libraries via `transform()`/`satisfies()` rather than rebuilding every helper.
- **Validate first, then transform.**
- **Mutable fluent API by design** — chained calls mutate and return the same instance (like Laravel's query builder); use `clone()` to fork a configured validator. This is a settled decision and is not revisited for v1.0.

## Current behavior

- Optional by default (null allowed unless `required()`); form-safe coercion (empty string → null, not `0`/`false`); pipeline order guaranteed; fail-fast per field; schema validation aggregates errors across fields.
- Type factories are strict unless coercion is enabled: `isInt()` accepts integers, `isFloat()` accepts floats and integers (PHP's own `strict_types` int-to-float widening; JSON cannot express a whole-number float), `isArray()` accepts lists, `isAssociative()` accepts non-list arrays (plus the ambiguous empty array), and `isObject()` accepts `stdClass`.
- Structured errors keep exact `int|string` path segments as their source of truth and derive the dotted `getPath()` view. String path filters remain convenient; segment-list filters are available when keys contain dots, are empty, or must distinguish an integer index from a numeric string key.

## Road to v1.0

These are the changes that touch the public contract and are therefore best done before committing to API stability.

### 1. Error model (landed; verify during the release audit)

The unified structured error contract is in place:

- **Structured error codes** (e.g. `STRING_TOO_SHORT`, `INVALID_EMAIL`) for programmatic handling and i18n. Stable machine handles decouple error identity from human wording, so messages can be reworded freely after v1.0.
- **Full error paths** for nested structures (`user.address.street`) instead of bare leaf keys.
- **Message placeholders** (`{value}`, `{index}`, `{min}`, …) for custom messages and localization.
- A single `ValidationException::getErrors(string|array|null $path = null)` accessor returning a flat list of `ValidationError` objects. `ValidationError` is JSON-serializable, exposes both `getPath()` and `getSegments()`, and accepts a segment list in its constructor for exact custom paths.

Before v1.0, perform one final naming/parameter audit of `ValidationCode` and the JSON error shape. After v1.0, codes and serialized field names are stable integration contracts.

### 2. Deprecation cleanup

Remove the deprecated aliases `addValidation`, instance `allOf`, instance `anyOf`, instance `not`, and `oneOf`. The combinators live on `Validator::`; allowed-value validation uses `in()`. Update call sites, tests, and migration notes.

### 3. Schema posture completion

- `strict()` — reject undeclared keys (completes the `default` / `passthrough()` / `strict()` trio).
- `isInstance(ClassName::class)` — validate object instances (closes a type-coverage gap alongside scalars, arrays, and enums).

### 4. Public surface and typing contract

- Audit every class in the runtime namespace and mark implementation-only types `@internal` or remove them before the namespace becomes stable. `PipelineType` was removed because it was unused internal metadata; `MixedValidator`, `PipelineStep`, and `PipelineContext` are implementation details.
- Keep the v1.0 promise precise: Lemmon provides runtime validation and transformation. Because arbitrary `transform()` calls can change output type and validators are mutable, `validate()` and the data element of `tryValidate()` remain `mixed`; static schema-output inference is not part of the v1.0 contract.
- Decide whether subclassing `FieldValidator` is supported. The current extension point is `satisfies()`/`transform()`, so unsupported inheritance should be made explicit before v1.0 rather than left accidental.

### 5. Release hygiene

- Docs refresh: ensure `README.md`, `docs/`, and `llms.txt` match the final v1.0 surface; add migration notes for the deprecation removals.
- Run a focused mutation-testing pilot over coercion, null/default/required flow, and structured path aggregation before freezing their behavior.
- Record a small performance baseline for `validate()`, `tryValidate()`, and nested schema failures. `tryValidate()` currently uses exceptions internally, so failure-heavy workloads should be measured even though no public optimization is required for v1.0.
- Confirm the PHP 8.3–8.5 CI matrix and lowest-supported dependency installation before tagging the release candidate.

## Post-1.0 (additive, against a frozen API)

None of these touch the public contract, so they are strictly better landed after v1.0.

### Schema composition & structure

- `partial()`, `pick()`, `omit()`, `merge()` for schema variations (PATCH requests, API versioning).
- `forbidKeys(array $keys, ?string $message = null)` — explicit key deny-listing.
- `patternProperties()`, `propertyNames()` — key validation.
- `dependencies()` — cross-field dependencies.
- Tuple validation / `additionalItems()` for arrays.

### Conditional / discriminated schemas

- `Validator::conditional($discriminator, [...])` for polymorphic data (select a schema based on input).

### Quality (ongoing)

- Property-based tests for core validators (string patterns, numeric constraints).
- Performance benchmarking for hot paths (`validate`, `tryValidate`, schema validation).

### Known limitations

- **`uniqueField()` dedup key collides distinct resources.** Uniqueness is keyed on `serialize($fieldValue)`, but PHP serializes every resource to `i:0;`, so two _distinct_ resource handles compare equal and are wrongly reported as duplicates. (Related: the `serialize()` try/catch added for unserializable values only takes its object-identity branch for closures/objects — the non-object `item#index` fallback is effectively unreachable, since `serialize()` does not throw for resources.) `uniqueField` is meant for scalar fields; the fix is either a value-equality key that distinguishes non-serializable non-objects, or documenting the scalar-only intent. Pre-existing; surfaced during the structured-error work.

## Beyond core (likely separate packages)

Kept for context; intentionally out of the lightweight core scope. Adoption is a secondary concern, so only pursue the cheap, well-aligned ones.

- **Framework middleware** (e.g. a PSR-15 / Laravel / Symfony bridge) — the most reasonable of these and an adoption aid. Thin glue: wrap `validate()`, map `ValidationException` to a 422 response. Low effort, but only worthwhile after the structured error model lands (the point is machine-readable errors in the response).
- **Schema export to a standard format** (OpenAPI / JSON Schema) for frontend/backend sync — one-directional only. Depends on built-in constraints carrying machine-readable metadata, which the error model introduces; closures from `satisfies()`/`transform()` are not introspectable, so export covers only the declarative surface. (No `fromJson()` / round-trip: arbitrary closures cannot be reconstructed, and a lossy serializer that silently drops custom rules would be a footgun.)
- **Database-driven validation** (e.g. `lemmon/validator-doctrine`) — a `fromDatabaseTable()` generator that derives a validator from DB schema. Niche, heavy deps; far-future. (Rules that _query_ the DB need no core support — use `satisfies()`.)

## Not planned

- `filled()` — use `required()` + `notEmpty()` (optionally `nullifyEmpty()` or `pipe('trim')`).
- `optional()` / `nullable()` — redundant; fields are optional by default.
- `when()` — use external control flow or context-aware `satisfies()`.
- Specialized string / identifier / type-conversion helpers (trim/slugify/case, cuid2/nanoid/ulid, `toDateTime()`, JSON decode) — use `transform()`/`satisfies()` with external libraries.
- Validation analytics, A/B testing, result caching — out of scope for a focused validation core.
