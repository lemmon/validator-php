# Lemmon Validator Project Summary

This project is a lightweight, fluent validation library for PHP, inspired by Valibot and Zod. It provides a structured way to validate various data types, including associative arrays, `stdClass` objects, plain arrays, strings, integers, and booleans. It features a symmetrical and predictable API for handling different data structures.

## Key Components:

*   **`Validator.php`**: Acts as a factory for creating different validator instances (e.g., `isAssociative()`, `isObject()`, `isArray()`).
*   **`AssociativeValidator.php`**: Handles validation for associative arrays. It can be configured with `->coerce()` to automatically convert a `stdClass` object into an associative array before validation.
*   **`ObjectValidator.php`**: Handles validation for `stdClass` objects. It can be configured with `->coerce()` to automatically convert an associative array into a `stdClass` object before validation.
*   **`FieldValidator.php`**: An abstract base class for all individual validators. It defines common validation methods like `required()`, `default()`, `coerce()`, and `oneOf()`. It implements a unified `validate()` (throws exception) and `tryValidate()` (returns result tuple) pattern.
*   **Specific Validators (e.g., `StringValidator.php`, `IntValidator.php`, `ArrayValidator.php`)**: Implement type-specific validation and coercion logic.
*   **`ValidationException.php`**: Custom exception class used to report validation errors, capable of holding a nested structure of error messages.

## Development Setup:

*   **Dependencies**: Managed by Composer (`composer.json`).
*   **Testing**: Uses Pest PHP (`tests/ValidatorTest.php`). The test suite is run with `composer test`.
*   **Code Style**: Enforced by PHP-CS-Fixer. Can be checked with `composer lint` and fixed with `composer fix`.
*   **Static Analysis**: Performed by PHPStan. Can be run with `composer analyse`.
*   **Debugging**: `symfony/var-dumper` is included as a dev dependency.
*   **Error Handling**: `symfony/error-handler` is included as a dev dependency.