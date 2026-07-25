# Lemmon Validator

[![CI](https://github.com/lemmon/validator-php/actions/workflows/ci.yml/badge.svg)](https://github.com/lemmon/validator-php/actions/workflows/ci.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/lemmon/validator.svg)](https://packagist.org/packages/lemmon/validator)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Lemmon Validator is a PHP library with no third-party Composer dependencies for validating and
transforming scalar values, indexed arrays, and nested schemas for associative arrays or `stdClass`
objects through a fluent API. It combines form-safe coercion, ordered pipelines, and structured errors
for exception and non-exception workflows.

> [!NOTE]
> Lemmon Validator is pre-1.0 and under active development. Minor releases may contain breaking
> changes; review the [changelog](CHANGELOG.md) before upgrading.

## Installation

```bash
composer require lemmon/validator
```

**Requirements:** PHP 8.3 or higher with the `mbstring` extension

## Quick Start

Validators accept `null` by default. Use `required()` when a value must be present.

```php
use Lemmon\Validator\ValidationException;
use Lemmon\Validator\Validator;

$input = [
    'name' => 'Ada Lovelace',
    'age' => '36',
    'email' => 'ada@example.com',
];

$userSchema = Validator::isAssociative([
    'name' => Validator::isString()
        ->pipe('trim')
        ->notEmpty()
        ->required(),
    'age' => Validator::isInt()
        ->coerce()
        ->min(18), // Optional; "36" becomes 36
    'email' => Validator::isString()
        ->pipe('trim')
        ->email()
        ->required(),
]);

// Non-exception workflow
[$valid, $user, $errors] = $userSchema->tryValidate($input);
if (!$valid) {
    foreach ($errors as $error) {
        echo $error->getPath() . ': ' . $error->getMessage() . PHP_EOL;
    }
}

// Exception workflow
try {
    $user = $userSchema->validate($input);
} catch (ValidationException $exception) {
    $allErrors = $exception->getErrors();
    $emailErrors = $exception->getErrors('email');
}
```

## Features

- **No third-party Composer dependencies** — the runtime depends only on PHP and `mbstring`
- **Strict runtime type validation** for strings, integers, floats, booleans, indexed arrays,
  associative arrays, and `stdClass` objects; conversions are opt-in through `coerce()`
- **Form-safe optional fields** — validators accept `null` unless `required()`; numeric, boolean, and
  container coercion turns an empty string into `null`, while strings can opt in with `nullifyEmpty()`
- **Predictable processing** — pipeline steps execute in chain order, then `default()` and `required()`
  resolve the final value
- **Composable schemas** — validate nested data, omit undeclared fields by default, or retain them
  explicitly with `passthrough()`
- **Structured errors** — stable codes, dotted display paths, exact path segments, messages, and
  parameters support programmatic handling, i18n, path filtering, and JSON serialization
- **Extensible rules** — use PHP callables with `transform()`, `pipe()`, and `satisfies()`, or compose
  validators with logical combinators

## Philosophy

Lemmon Validator focuses on core validation and predictable schema behavior rather than reimplementing
every specialized rule. Its transformation and custom-validation methods accept ordinary PHP callables,
so application-specific behavior can be composed without framework integrations or custom validator
subclasses.

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

### Factory Reference

- [Validator Factory](docs/api-reference/validator-factory.md)

### Examples

- [Form Validation](docs/examples/form-validation.md)

### For AI Agents

- [`llms.txt`](llms.txt) — Technical specification with API signatures, parameters, and core behavior

## Security

Please report suspected vulnerabilities privately by following the
[Security Policy](SECURITY.md). Do not disclose security issues in the public issue tracker.

## Contributing

Contributions are welcome. Read the [Contributing Guide](CONTRIBUTING.md), or
[open an issue](https://github.com/lemmon/validator-php/issues) to report a bug or propose an improvement.

## License

Lemmon Validator is licensed under the [MIT License](LICENSE).
