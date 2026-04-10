# Lemmon Validator

PHP validation library inspired by Valibot and Zod. Type-safe, fluent API for primitives, arrays, and objects.

## Project Context

Core validation logic lives in `src/Lemmon/Validator/`. Tests in `tests/` follow `XValidatorTest.php` naming. Key project files:

- `llms.txt` - Technical spec for external/consumer use; API signatures, null handling, transformation types
- `ROADMAP.md` - Strategic planning and checkboxes
- `TASKS.md` - Immediate task pool (keep short, no numbering)
- `CHANGELOG` - Completed work
- `IDEAS` - Exploration

## Architecture

- **Namespace:** `Lemmon\Validator` (runtime), `Lemmon\Tests` (tests)
- **Core:** `Validator` static factory; `FieldValidator` base; `ValidationException` for errors
- **Validators:** `isString`, `isInt`, `isFloat`, `isBool`, `isArray`, `isAssociative`, `isObject`
- **Shared:** `NumericConstraintsTrait` (min, max, multipleOf, etc.); `PipelineType` enum; variant enums `IpVersion`, `Base64Variant`, `UuidVariant` for format methods
- **String formats:** email, URL, UUID, IP, hostname, domain, time, base64, hex, regex, datetime, date
- **Schema validation:** AssociativeValidator/ObjectValidator with nested error aggregation; shared `SchemaValidatorOptionsTrait` (`coerceAll`, `passthrough`, schema clone helper); `passthrough()` keeps undeclared keys/properties (unvalidated); default output is schema keys only
- **Logical combinators:** `Validator::allOf`, `anyOf`, `not`; instance `satisfiesAny`, `satisfiesAll`, `satisfiesNone`; `const()` for single allowed value; `enum()` for BackedEnum
- **Behavior:** Optional by default (null allowed unless `required()`); form-safe coercion (empty string → null, not 0/false); pipeline order guaranteed; fail-fast per field; `satisfies()` accepts validators or callables with `(value, key, input)`; extend via `satisfies()`, not custom validators

## Build, Test, and Development Commands

- `composer test` - Run Pest test suite
- `composer lint` / `composer format` - Mago linting and formatting (dev dependency)
- `composer analyse` - PHPStan at max level
- `composer platform-check` - Verify PHP 8.3 and extension requirements
- `composer check` - Run all checks (platform, format, lint, Prettier, test, analyse); use before PR
- `npm run format` - Prettier for YAML, JSON, Markdown
- `npm run check` - Alias for `composer check`
- Pre-commit hook (Husky): Prettier check, Mago format --staged, Mago lint --staged

`config.platform.php` is set to `8.3.0` so dependency resolution targets PHP 8.3; `composer update`/`install` will not add packages that require PHP above 8.3.

Dev tooling: symfony/var-dumper, symfony/error-handler, ergebnis/composer-normalize.

## Coding Style & Formatting

PSR-12 for PHP. Mago for lint/format (`composer lint`, `composer format`). Prettier (`.prettierrc`) for YAML, JSON, Markdown. Add PHPDoc where behavior is non-obvious. Stick to ASCII punctuation in code and docs (e.g. `--` not em dash) so diffs stay predictable. Emojis sparingly.
