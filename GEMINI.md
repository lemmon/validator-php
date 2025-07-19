# Lemmon Validator Project Summary

This project is a lightweight, fluent validation library for PHP, inspired by Valibot and Zod. It provides a structured way to validate various data types, including associative arrays, plain arrays, strings, integers, and booleans, with support for coercion, required fields, default values, and `oneOf` constraints.

## Key Components:

*   **`Validator.php`**: Acts as a factory for creating different validator instances (e.g., `isAssociative()`, `isArray()`, `isString()`).
*   **`SchemaValidator.php`**: Handles validation for associative arrays, allowing definition of a schema with `FieldValidator` instances. It supports `validate()` (throws exception) and `tryValidate()` (returns status, data, errors).
*   **`FieldValidator.php`**: An abstract base class for individual field validators. It defines common validation methods like `required()`, `default()`, `coerce()`, and `oneOf()`. Concrete validators extend this class and implement `coerceValue()` and `validateType()`.
*   **Specific Validators (e.g., `StringValidator.php`, `IntValidator.php`, `BoolValidator.php`, `ArrayValidator.php`)**: Implement the abstract methods from `FieldValidator.php` to provide type-specific validation and coercion logic.
*   **`ValidationException.php`**: Custom exception class used to report validation errors.

## Development Setup:

*   **Dependencies**: Managed by Composer (`composer.json`).
*   **Testing**: Uses Pest PHP (`tests/ValidatorTest.php`).
*   **Code Style**: Enforced by PHP-CS-Fixer.
*   **Static Analysis**: Performed by PHPStan.
*   **Local Testing**: A `temp/` directory is used for ad-hoc CLI tests, with a `_bootstrap.php` file for common setup (Composer autoloader, Symfony ErrorHandler). The `temp/` directory is excluded from Git via `.gitignore`.
*   **Debugging**: `symfony/var-dumper` is included as a dev dependency for easy debugging.
*   **Error Handling**: `symfony/error-handler` is included as a dev dependency and registered in the bootstrap for improved error reporting.
