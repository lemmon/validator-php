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

**Requirements:** PHP 8.1 or higher

## About

**Philosophy: "Simple and Minimal with Extensibility Over Reinvention"**

Rather than reimplementing every possible transformation or validation rule, Lemmon Validator provides a solid foundation with generic `transform()` and `pipe()` methods that integrate seamlessly with PHP's ecosystem. Need complex string transformations? Plug in Laravel's `Str` class through our transformation system. Need advanced array operations? Connect Laravel Collections via our fluent API. The library focuses on what it does best: type-safe validation with excellent developer experience, while enabling you to leverage the entire PHP ecosystem.

**Key Design Principles:**

- **Type-Safe Architecture**: Modern PHP 8.1+ enums provide IDE autocomplete, refactoring safety, and eliminate magic strings throughout the codebase
- **Smart Null Handling**: Validations skip `null` unless `required()`, transformations always execute, and method order is independent for intuitive behavior
- **Form Safety First**: Empty strings coerce to `null` (not dangerous `0`/`false`) to prevent real-world issues like accidental zero bank balances
- **Fluent API with Execution Order Guarantee**: Validation rules read like natural language and execute in the exact order written -- `Validator::isString()->pipe('trim')->nullifyEmpty()->required()`
- **Comprehensive Error Collection**: All validation errors are collected, not just the first failure
- **Type-Aware Transformations**: Intelligent transformation system that maintains type context and handles coercion automatically
- **Extensible Architecture**: Generic transformation methods work with any PHP callable or external library

## Features

- **Type-safe architecture** - PHP 8.1+ enums with IDE autocomplete, refactoring safety, and zero magic strings
- **Smart null handling** - validations skip `null` unless `required()`, transformations always execute, order-independent behavior
- **Type-safe validation** for strings, integers, floats, arrays, and objects
- **Fluent, chainable API** with guaranteed execution order -- methods execute exactly as written in the chain
- **Comprehensive error collection** with detailed, structured feedback
- **Intuitive custom validation** with `satisfies()` method and optional error messages
- **Logical combinators** (`Validator::allOf()`, `Validator::anyOf()`, `Validator::not()`) for complex validation logic
- **Form-safe coercion** - empty strings become `null` (not dangerous `0`/`false`) for real-world safety
- **Accurate schema validation** - results only include provided fields and fields with defaults (no unexpected properties)
- **Universal transformations** (`transform()`, `pipe()`) for post-validation data processing
- **Null-safe operations** with `nullifyEmpty()` method for consistent empty value handling

## Quick Start

```php
use Lemmon\Validator;

// Simple validation with form-safe coercion
$email = Validator::isString()
    ->email()
    ->nullifyEmpty() // Empty strings become null (form-safe)
    ->validate('user@example.com');

// Schema validation with custom logic
$userSchema = Validator::isAssociative([
    'name' => Validator::isString()->required(),
    'age' => Validator::isInt()->min(18)->coerce(),
    'email' => Validator::isString()->email()->nullifyEmpty(),
    'password' => Validator::isString()->satisfies(fn($v) => strlen($v) >= 8, 'Password too short')
]);

// Tuple-based validation (no exceptions)
[$valid, $user, $errors] = $userSchema->tryValidate($input);

// Exception-based validation
$user = $userSchema->validate($input); // Throws ValidationException on failure
```

## Documentation

### Getting Started
- [Installation & Setup](docs/getting-started/installation.md)
- [Basic Usage](docs/getting-started/basic-usage.md)
- [Core Concepts](docs/getting-started/core-concepts.md)

### Validation Guides
- [String Validation](docs/guides/string-validation.md) -- Email, URL, patterns, length constraints
- [Numeric Validation](docs/guides/numeric-validation.md) -- Integers, floats, ranges, constraints
- [Array Validation](docs/guides/array-validation.md) -- Indexed arrays and item validation
- [Object & Schema Validation](docs/guides/object-validation.md) -- Complex nested structures
- [Custom Validation](docs/guides/custom-validation.md) -- User-defined functions and business logic
- [Error Handling](docs/guides/error-handling.md) -- Working with validation errors

### API Reference
- [Validator Factory](docs/api-reference/validator-factory.md)

### Examples
- [Form Validation](docs/examples/form-validation.md)

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Links

- [Packagist](https://packagist.org/packages/lemmon/validator)
- [GitHub Repository](https://github.com/lemmon/validator-php)
- [Issue Tracker](https://github.com/lemmon/validator-php/issues)
- [Changelog](CHANGELOG.md)
