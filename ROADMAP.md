# Lemmon Validator — Roadmap

Single source of truth for planned and considered work. Completed work lives in `CHANGELOG.md`.

## Philosophy

- **The `pipe()`/`transform()` model with coercion is the core value to protect.** It is the library's defining feature; weigh every change first against whether it keeps that flow clean and pleasant to use, and never trade its ergonomics away for other goals.
- **Extensibility over reinvention** — integrate external libraries via `transform()`/`satisfies()` rather than rebuilding every helper.
- **Validate first, then transform.**
- **Mutable fluent API by design** — chained calls mutate and return the same instance (like Laravel's query builder); use `clone()` to fork a configured validator. This is a settled decision and is not revisited for v1.0.

## Current behavior

- Optional by default (null allowed unless `required()`); form-safe coercion (empty string → null, not `0`/`false`); pipeline order guaranteed; fail-fast per field; schema validation aggregates errors across fields.

## Road to v1.0

These are the changes that touch the public contract and are therefore best done before committing to API stability.

### 1. Error model (the v1.0 centerpiece)

One unified design covering three things that share the same error object. The shape of what `ValidationException` exposes is a public contract, so it must be settled before v1.0.

- **Structured error codes** (e.g. `STRING_TOO_SHORT`, `INVALID_EMAIL`) for programmatic handling and i18n. Stable machine handles decouple error identity from human wording, so messages can be reworded freely after v1.0.
- **Full error paths** for nested structures (`user.address.street`) instead of bare leaf keys.
- **Message placeholders** (`{value}`, `{index}`, `{min}`, …) for custom messages and localization; pair with global/default message templates for consistent branding.
- Expose the errors through a single accessor, `ValidationException::getErrors(?string $path = null)`, returning the `ValidationError` value objects (a flat list; an optional path filters to one field and its subtree). The legacy nested-message view and the separate flattened helpers were dropped in favor of this one method. _(Landed; `ValidationError` is `JsonSerializable`.)_

### 2. Deprecation cleanup

Remove the deprecated instance aliases `addValidation`, `allOf`, `anyOf`, `not`; the combinators live on `Validator::` (now backed by `MixedValidator`). Update call sites and docs.

### 3. Schema posture completion

- `strict()` — reject undeclared keys (completes the `default` / `passthrough()` / `strict()` trio).
- `isInstance(ClassName::class)` — validate object instances (closes a type-coverage gap alongside scalars, arrays, and enums).

### Release hygiene

- Docs refresh: ensure `README.md`, `docs/`, and `llms.txt` match the final v1.0 surface; add migration notes for the deprecation removals.

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

- Mutation testing pilot (Infection + baseline config, documented local run).
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
