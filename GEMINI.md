# Lemmon Validator Project Summary

This project is a comprehensive, fluent validation library for PHP, inspired by Valibot and Zod. It provides a structured way to validate various data types, including associative arrays, `stdClass` objects, plain arrays, strings, integers, floating-point numbers, and booleans. It features a symmetrical and predictable API for handling different data structures with advanced validation capabilities.

## Key Components:

### Core Architecture:
*   **`Validator.php`**: Acts as a factory for creating different validator instances (e.g., `isAssociative()`, `isObject()`, `isArray()`, `isString()`, `isInt()`, `isFloat()`, `isBool()`).
*   **`FieldValidator.php`**: An abstract base class for all individual validators. It defines common validation methods like `required()`, `default()`, `coerce()`, `oneOf()`, and logical combinators (`allOf()`, `anyOf()`, `not()`). It implements a unified `validate()` (throws exception) and `tryValidate()` (returns result tuple) pattern with comprehensive error collection.
*   **`ValidationException.php`**: Custom exception class used to report validation errors, capable of holding a nested structure of error messages.

### Structure Validators:
*   **`AssociativeValidator.php`**: Handles validation for associative arrays with schema definition. It can be configured with `->coerce()` to automatically convert a `stdClass` object into an associative array before validation.
*   **`ObjectValidator.php`**: Handles validation for `stdClass` objects with schema definition. It can be configured with `->coerce()` to automatically convert an associative array into a `stdClass` object before validation.
*   **`ArrayValidator.php`**: Handles validation for plain indexed arrays with optional item validation.

### Type Validators:
*   **`StringValidator.php`**: Comprehensive string validation with format validators (`email()`, `url()`, `uuid()`, `ip()`, `datetime()`, `date()`), length constraints (`minLength()`, `maxLength()`, `length()`), and pattern matching (`pattern()`).
*   **`IntValidator.php`**: Integer validation with numeric constraints (`min()`, `max()`, `multipleOf()`, `positive()`, `negative()`) via `NumericConstraintsTrait`.
*   **`FloatValidator.php`**: Floating-point number validation with numeric constraints (`min()`, `max()`, `multipleOf()`, `positive()`, `negative()`) via `NumericConstraintsTrait`.
*   **`BoolValidator.php`**: Boolean validation with smart coercion support.

### Shared Components:
*   **`NumericConstraintsTrait.php`**: Shared trait providing common numeric validation constraints for both `IntValidator` and `FloatValidator`, eliminating code duplication and ensuring consistent behavior.

## Advanced Features:

*   **Logical Combinators**: Complex validation logic with `allOf()`, `anyOf()`, and `not()` methods for advanced rule composition.
*   **Context-Aware Validation**: Custom validators receive `(value, key, input)` parameters for sophisticated validation logic.
*   **Comprehensive Error Collection**: Validators collect all validation errors instead of stopping at the first failure, providing complete feedback.
*   **Smart Type Coercion**: Intelligent type conversion with configurable coercion rules.
*   **Fluent API**: Chainable method calls for readable and maintainable validation code.

## Development Setup:

*   **Dependencies**: Managed by Composer (`composer.json`).
*   **Testing**: Uses Pest PHP with organized test suite across multiple focused test files. The test suite is run with `composer test`.
*   **Code Quality**:
    *   **Code Style**: Enforced by PHP-CS-Fixer. Can be checked with `composer lint` and fixed with `composer fix`.
    *   **Static Analysis**: Performed by PHPStan at maximum level. Can be run with `composer analyse`.
    *   **Test Coverage**: 57 tests with 154 assertions covering all functionality.
*   **Development Tools**:
    *   **Debugging**: `symfony/var-dumper` is included as a dev dependency.
    *   **Error Handling**: `symfony/error-handler` is included as a dev dependency.

## Test Organization:

The test suite is organized into focused files for maintainability:
*   `AssociativeValidatorTest.php` - Associative array validation
*   `ObjectValidatorTest.php` - stdClass object validation
*   `ArrayValidatorTest.php` - Plain array validation
*   `StringValidatorTest.php` - String validation and formats
*   `IntValidatorTest.php` - Integer validation
*   `FloatValidatorTest.php` - Float validation
*   `FieldValidatorTest.php` - Base validator functionality and combinators
*   `NumericConstraintsTraitTest.php` - Trait-specific tests
