# Lemmon Validator - Development Roadmap

Focused on core validation primitives, consistent pipelines, and extensibility via `transform()` and `satisfies()` rather than rebuilding every helper.

## Philosophy

- Extensibility over reinvention -- integrate external libraries for specialized transforms and validators.
- Validate first, then transform.

## Current Behavior

- Validation fails fast per field; schema validation aggregates errors across fields.

## Next Minor Release

- [ ] Universal allowed-value validators: `enum()` (PHP BackedEnum) and `const()` (single allowed value)
- [x] Schema output key remapping: `outputKey(string $key)`
- [ ] Structured error codes in validation errors (backward compatible)
- [ ] ArrayValidator `uniqueField(string $fieldName, ?string $message = null)`
- [ ] Mutation testing pilot (Infection + baseline)
- [ ] Property-based tests for core validators

## Future (Schema and Structure)

- [ ] Schema composition: `partial()`, `pick()`, `omit()`, `merge()`
- [ ] Unknown key policies: `strict()` (reject undeclared keys) and `passthrough()` (preserve undeclared keys)
- [ ] Explicit key deny-listing: `forbidKeys(array $keys, ?string $message = null)`
- [ ] Key validation: `patternProperties()`, `propertyNames()`
- [ ] Cross-field dependencies: `dependencies()`
- [ ] Tuple validation / `additionalItems()` for arrays

## Quality (Ongoing)

- [ ] Performance benchmarking for hot paths

## Not Planned

- [ ] ~~`filled()`~~ -- Use `required()` + `notEmpty()` (optionally `nullifyEmpty()` or `pipe('trim')`)
- [ ] ~~`optional()` / `nullable()`~~ -- Redundant; fields are optional by default
- [ ] ~~`when()`~~ -- Use external control flow or context-aware `satisfies()`
- [ ] ~~Specialized string helpers (trim/slugify/case)~~ -- Use `transform()` with external libraries
- [ ] ~~Identifier validators (cuid2/nanoid/ulid)~~ -- Use external libraries via `satisfies()`

## Notes

- Completed work lives in `CHANGELOG.md`.
