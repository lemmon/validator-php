# Installation & Setup

## Requirements

- **PHP 8.1 or higher**
- **Composer** for dependency management

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

use Lemmon\Validator;

// Ready to use!
$validator = Validator::isString();
```

## Verification

Verify your installation by running a simple validation:

```php
<?php
require_once 'vendor/autoload.php';

use Lemmon\Validator;

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
```

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage
composer test -- --coverage
```

### Code Quality Tools

```bash
# Check code style
composer lint

# Fix code style issues
composer fix

# Run static analysis
composer analyse
```

## Next Steps

- 📖 [Basic Usage](basic-usage.md) - Learn the fundamentals
- 💡 [Core Concepts](core-concepts.md) - Understand the architecture
- 🎯 [String Validation Guide](../guides/string-validation.md) - Start with string validation
