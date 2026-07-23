# Installation & Setup

## Requirements

- **PHP 8.3 or higher**
- **PHP `mbstring` extension**
- **Composer** for dependency management

> Note: The library ships with `declare(strict_types=1);`. Validation failures throw `ValidationException`; use `coerce()` if you need form-friendly conversions.

## Installation

Install the Lemmon Validator via Composer:

```bash
composer require lemmon/validator
```

## Autoloading

The package follows PSR-4 autoloading standards. If you're using Composer's autoloader, you can start using the validator immediately:

```php
<?php
require_once 'vendor/autoload.php';

use Lemmon\Validator\Validator;

// Ready to use!
$validator = Validator::isString();
```

## Verification

Verify your installation by running a simple validation:

```php
<?php
require_once 'vendor/autoload.php';

use Lemmon\Validator\Validator;

try {
    $result = Validator::isString()->email()->validate('test@example.com');
    echo "✅ Installation successful! Email validation works.\n";
    echo "Result: " . $result . "\n";
} catch (Exception $e) {
    echo "❌ Installation issue: " . $e->getMessage() . "\n";
}
```

## Development Installation

If you want to contribute to the project or run tests, clone the repository:

```bash
git clone https://github.com/lemmon/validator-php.git
cd validator-php
composer install
npm install
```

Running the complete development checks also requires Node.js and npm for Prettier and the Git hooks.

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage
composer test -- --coverage
```

### Code Quality Tools

```bash
# Run every project check
composer check

# Format and lint PHP
composer format
composer lint

# Run static analysis
composer analyze

# Format Markdown, YAML, and JSON
npm run format
```

## Next Steps

- [Basic Usage](basic-usage.md) — Learn the fundamentals
- [Core Concepts](core-concepts.md) — Understand the architecture
- [String Validation Guide](../guides/string-validation.md) — Start with string validation
