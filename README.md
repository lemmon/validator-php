# Lemmon Validator

[![CI](https://github.com/lemmon/validator-php/actions/workflows/ci.yml/badge.svg)](https://github.com/lemmon/validator-php/actions/workflows/ci.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/lemmon/validator.svg)](https://packagist.org/packages/lemmon/validator)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> [!NOTE]
> This library is in active development. The API may change in future versions as we refine and improve the developer experience based on real-world usage and feedback.

Lemmon Validator is a comprehensive, fluent validation and data processing library for PHP that prioritizes developer experience, type safety, and real-world practicality. Inspired by modern validation libraries like Valibot and Zod, it brings a chainable, readable API to PHP for validation, transformation, and sanitization with intelligent error handling and form-safe defaults.

## Installation

```bash
composer require lemmon/validator
```

**Requirements:** PHP 8.3 or higher

## Quick Start

All runtime classes live under the `Lemmon\Validator` namespace.

```php
use Lemmon\Validator\Validator;

// Simple validation with form-safe coercion
$email = Validator::isString()
    ->nullifyEmpty() // Empty strings become null (form-safe)
    ->email()
    ->validate('user@example.com');

// Schema validation with custom logic
$userSchema = Validator::isAssociative([
    'name' => Validator::isString()->required(),
    'age' => Validator::isInt()->min(18)->coerce(),
    'email' => Validator::isString()->nullifyEmpty()->email(),
    'password' => Validator::isString()->minLength(8, 'Password too short')
]);

// Tuple-based validation (no exceptions)
[$valid, $user, $errors] = $userSchema->tryValidate($input);
if (!$valid) {
    // $errors is a flat list of ValidationError objects (path, code, message, params)
    foreach ($errors as $error) {
        echo "{$error->getPath()}: {$error->getMessage()}\n";
    }
}

// Exception-based validation
try {
    $user = $userSchema->validate($input);
} catch (\Lemmon\Validator\ValidationException $e) {
    $all = $e->getErrors();             // every ValidationError
    $nameErrors = $e->getErrors('name'); // just the 'name' field (and its subtree)
}
```

## Features

- **Type-safe validation** for strings, integers, floats, arrays, and objects, powered by PHP enums for IDE autocomplete, refactoring safety, and zero magic strings
- **Fluent, chainable API** with a guaranteed execution order — methods run exactly as written (`->pipe('trim')->nullifyEmpty()->required()`)
- **Smart null handling** — validations skip `null` unless `required()`; `transform()`/`pipe()`/`nullifyEmpty()` skip `null` by default
- **Form-safe coercion** — empty strings become `null` (not dangerous `0`/`false`); opt into `coerce()` for form-friendly type conversions
- **Universal transformations** (`transform()`, `pipe()`) that plug in any PHP callable or external library
- **Custom validation** with `satisfies()`, plus logical combinators (`satisfiesAll()`/`satisfiesAny()`/`satisfiesNone()`)
- **Exact-value and enum matching** — `const()` for single values, `enum()` for `BackedEnum`/`UnitEnum` (available on all validators)
- **Last-resort defaults** — `default()` fills `null` after the pipeline, before `required()` enforces presence
- **Structured errors** — `getErrors()` returns `ValidationError` objects with a stable `code` (see `ValidationCode`), dotted `path`, `message`, and `params` for programmatic handling and i18n; pass a path (`getErrors('address')`) to filter to one field and its subtree. `ValidationError` is `JsonSerializable`, so the list drops straight into a JSON API response
- **Fail-fast per field, aggregated per schema** — each validator stops at its first failure while schema validation collects errors across all fields
- **Accurate schema results** — output includes only provided fields and fields with defaults, never unexpected properties
- **Strict typing throughout** — every file uses `declare(strict_types=1);`

## Philosophy

**Simple and minimal, with extensibility over reinvention.** Rather than reimplementing every possible transformation or validation rule, Lemmon Validator provides a solid foundation with generic `transform()` and `pipe()` methods that integrate seamlessly with PHP's ecosystem. Need complex string transformations? Plug in Laravel's `Str` class. Need advanced array operations? Connect Laravel Collections. The library focuses on what it does best — type-safe validation with excellent developer experience — while letting you leverage the entire PHP ecosystem.

## Documentation

### Getting Started

- [Installation & Setup](docs/getting-started/installation.md)
- [Basic Usage](docs/getting-started/basic-usage.md)
- [Core Concepts](docs/getting-started/core-concepts.md)

### Validation Guides

- [String Validation](docs/guides/string-validation.md) — Email, URL, patterns, length constraints
- [Numeric Validation](docs/guides/numeric-validation.md) — Integers, floats, ranges, constraints
- [Array Validation](docs/guides/array-validation.md) — Indexed arrays and item validation
- [Object & Schema Validation](docs/guides/object-validation.md) — Complex nested structures
- [Custom Validation](docs/guides/custom-validation.md) — User-defined functions and business logic
- [Error Handling](docs/guides/error-handling.md) — Working with validation errors

### API Reference

- [Validator Factory](docs/api-reference/validator-factory.md)

### Examples

- [Form Validation](docs/examples/form-validation.md)

### For AI Agents

- **`llms.txt`** - Complete technical specification with full API signatures, method parameters, and core concepts. Download this file and provide it to your AI agent for accurate code generation and assistance with the library.

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Links

- [Packagist](https://packagist.org/packages/lemmon/validator)
- [GitHub Repository](https://github.com/lemmon/validator-php)
- [Issue Tracker](https://github.com/lemmon/validator-php/issues)
- [Changelog](CHANGELOG.md)
