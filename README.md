# Lemmon Validator

[![CI](https://github.com/lemmon/validator-php/actions/workflows/ci.yml/badge.svg)](https://github.com/lemmon/validator-php/actions/workflows/ci.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/lemmon/validator.svg)](https://packagist.org/packages/lemmon/validator)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A comprehensive, fluent validation library for PHP, inspired by Valibot and Zod. Build type-safe, readable validation schemas with chainable methods and intelligent error handling.

## ✨ Features

- 🔒 **Type-safe validation** for strings, integers, floats, arrays, and objects
- 🔗 **Fluent, chainable API** for readable and maintainable validation rules
- 📋 **Comprehensive error collection** with detailed, structured feedback
- ⚙️ **Custom validation functions** with context-aware parameters
- 🧩 **Logical combinators** (`Validator::allOf()`, `Validator::anyOf()`, `Validator::not()`) for complex validation logic
- 🔄 **Smart type coercion** with configurable behavior
- 🎯 **Schema validation** for nested data structures

## 🚀 Quick Start

```php
use Lemmon\Validator;

// Simple validation
$email = Validator::isString()
    ->email()
    ->validate('user@example.com');

// Schema validation
$userSchema = Validator::isAssociative([
    'name' => Validator::isString()->required(),
    'age' => Validator::isInt()->min(18),
    'email' => Validator::isString()->email(),
    'preferences' => Validator::isObject([
        'theme' => Validator::isString()->oneOf(['light', 'dark'])->default('light'),
        'notifications' => Validator::isBool()->default(true)
    ])
]);

[$valid, $user, $errors] = $userSchema->tryValidate($input);
```

## 📚 Documentation

### Getting Started
- 📖 [Installation & Setup](docs/getting-started/installation.md)
- 🎯 [Basic Usage](docs/getting-started/basic-usage.md)
- 💡 [Core Concepts](docs/getting-started/core-concepts.md)

### Validation Guides
- 🔤 [String Validation](docs/guides/string-validation.md) - Email, URL, patterns, length constraints
- 🔢 [Numeric Validation](docs/guides/numeric-validation.md) - Integers, floats, ranges, constraints
- 📋 [Array Validation](docs/guides/array-validation.md) - Indexed arrays and item validation
- 🏗️ [Object & Schema Validation](docs/guides/object-validation.md) - Complex nested structures
- ⚙️ [Custom Validation](docs/guides/custom-validation.md) - User-defined functions and business logic
- ❌ [Error Handling](docs/guides/error-handling.md) - Working with validation errors

### API Reference
- 🏭 [Validator Factory](docs/api-reference/validator-factory.md)

### Examples
- 📝 [Form Validation](docs/examples/form-validation.md)

## 📦 Installation

```bash
composer require lemmon/validator
```

**Requirements:** PHP 8.1 or higher

## 🏃‍♂️ Quick Examples

### String Validation with Formats
```php
// Email validation
$email = Validator::isString()->email()->validate('user@example.com');

// URL with custom message
$url = Validator::isString()
    ->url('Please provide a valid URL')
    ->validate('https://example.com');

// Pattern matching
$code = Validator::isString()
    ->pattern('/^[A-Z]{2}\d{4}$/', 'Code must be 2 letters + 4 digits')
    ->validate('AB1234');
```

### Numeric Validation
```php
// Integer with constraints
$age = Validator::isInt()
    ->min(18)
    ->max(120)
    ->validate(25);

// Enhanced coercion for form data
$quantity = Validator::isInt()
    ->coerce() // Empty strings become 0
    ->validate(''); // Returns: 0

// Float with precision
$price = Validator::isFloat()
    ->positive()
    ->multipleOf(0.01) // Cents precision
    ->coerce() // Empty strings become 0.0
    ->validate(19.99);
```

### Array Validation
```php
// Array filtering with auto-reindexing
$tags = Validator::isArray()
    ->filterEmpty() // Removes '', null but keeps 0, false, []
    ->validate(['php', '', 'javascript', null, 'react']);
// Returns: ['php', 'javascript', 'react'] (properly indexed)

// With item validation
$numbers = Validator::isArray()
    ->items(Validator::isInt()->positive())
    ->filterEmpty()
    ->validate([1, '', 2, null, 3]);
// Returns: [1, 2, 3]
```

### Custom Validation
```php
// Context-aware validation
$passwordConfirm = Validator::isString()->addValidation(
    function ($value, $key, $input) {
        return isset($input['password']) && $value === $input['password'];
    },
    'Password confirmation must match password'
);
```

### Advanced Logic
```php
// Logical combinators
$flexibleId = Validator::anyOf([
    Validator::isInt()->positive(),
    Validator::isString()->uuid()
]);

$strictUser = Validator::allOf([
    Validator::isAssociative(['name' => Validator::isString()]),
    Validator::isAssociative(['email' => Validator::isString()->email()])
]);

$notBanned = Validator::not(
    Validator::isString()->oneOf(['banned', 'suspended']),
    'User cannot be banned or suspended'
);
```

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🔗 Links

- [Packagist](https://packagist.org/packages/lemmon/validator)
- [GitHub Repository](https://github.com/lemmon/validator-php)
- [Issue Tracker](https://github.com/lemmon/validator-php/issues)
- [Changelog](CHANGELOG.md)
