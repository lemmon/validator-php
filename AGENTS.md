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
- **Schema validation:** AssociativeValidator/ObjectValidator with nested error aggregation
- **Logical combinators:** `Validator::allOf`, `anyOf`, `not`; instance `satisfiesAny`, `satisfiesAll`, `satisfiesNone`
- **Behavior:** Optional by default (null allowed unless `required()`); form-safe coercion (empty string → null, not 0/false); pipeline order guaranteed; fail-fast per field; `satisfies()` accepts validators or callables with `(value, key, input)`; extend via `satisfies()`, not custom validators

## Build, Test, and Development Commands

- `composer test` - Run Pest test suite
- `composer lint` / `composer format` - Mago linting and formatting (dev dependency)
- `composer analyse` - PHPStan at max level
- `composer platform-check` - Verify PHP 8.3 and extension requirements

`config.platform.php` is set to `8.3.0` so dependency resolution targets PHP 8.3; `composer update`/`install` will not add packages that require PHP above 8.3.

Dev tooling: symfony/var-dumper, symfony/error-handler, ergebnis/composer-normalize.

## Coding Style & Formatting

PSR-12 for PHP. Mago for lint/format (`composer lint`, `composer format`). Prettier (`.prettierrc`) for YAML, JSON, Markdown. Add PHPDoc where behavior is non-obvious. Stick to ASCII punctuation in code and docs (e.g. `--` not em dash) so diffs stay predictable. Emojis sparingly.

## Commit Message Guidelines

- Commit messages must follow Conventional Commits
- Subject format: `<type>(<scope>): <summary>`; use `<type>: <summary>` when scope is omitted
- Allowed `type` values: `feat`, `fix`, `refactor`, `perf`, `docs`, `test`, `build`, `ci`, `chore`, `revert`
- `scope` is optional but recommended when it narrows the area (e.g. `StringValidator`, `FieldValidator`, `AssociativeValidator`)
- Write summary lines in imperative mood, keep them concise (around 72 characters when practical), and omit trailing periods
- Keep each commit focused on one concern; include related tests or validation updates in the same commit when applicable
- Prefer bullet lists in commit bodies for concrete changes, with one logical change per bullet
- Commit body bullets should start with a capitalized imperative verb and omit trailing periods
- Avoid unnecessary noise in commit bodies; include only explicit, intentional, non-obvious updates
- Do not call out secondary artifact changes (for example lockfile refreshes) unless they carry non-obvious impact
- Add a short prose paragraph only when extra context is needed (rationale, tradeoffs, migration notes, risks, or non-obvious impact)
- Separate subject, body, and footers with blank lines
- Use optional footers in `Key: Value` format; preferred keys: `Refs`, `Closes`, `Fixes`, `PR`, `BREAKING CHANGE`
- Breaking changes must include a `BREAKING CHANGE:` footer
- Release notes belong in CHANGELOG, not commit body. Tag format: annotated `vX.Y.Z - <headline>`; details in CHANGELOG/releases
- Quick templates: `fix(scope): <imperative summary>` and `feat(scope): <imperative summary>`
