# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

## [0.3.0] - 2025-09-25

### Added

- **Complete String Validation Suite**: Added comprehensive string format validators:
  - `url()` - Validates URLs with support for various protocols
  - `uuid()` - Validates UUID strings (all versions)
  - `ip()` - Validates IPv4 and IPv6 addresses
  - `minLength()`, `maxLength()`, `length()` - String length constraints
  - `pattern()` - Regular expression pattern matching
  - `datetime()`, `date()` - Date and datetime format validation
- **FloatValidator**: New dedicated validator for floating-point numbers with full constraint support
- **NumericConstraintsTrait**: Shared trait for numeric validations eliminating code duplication between `IntValidator` and `FloatValidator`
- **Enhanced IntValidator**: Added numeric constraints (`min()`, `max()`, `multipleOf()`, `positive()`, `negative()`)
- **Logical Combinators**: Advanced validation logic with `allOf()`, `anyOf()`, and `not()` methods
- **Comprehensive Error Collection**: Validators now collect all validation errors instead of stopping at the first failure
- **Enhanced Validation Context**: Custom validators receive `(value, key, input)` parameters for context-aware validation

### Changed

- **BREAKING**: Renamed `NumberValidator` to `FloatValidator` for clearer semantics
- **BREAKING**: Renamed `Validator::isNumber()` to `Validator::isFloat()` for consistency with PHP types
- Enhanced `addValidation()` method to support context-aware custom validation functions
- Improved error messages and validation feedback throughout the library
- Refactored numeric constraint methods to use shared `NumericConstraintsTrait`

### Improved

- **Test Organization**: Split monolithic test suite into focused test files:
  - `AssociativeValidatorTest.php` - Associative array validation
  - `ObjectValidatorTest.php` - stdClass object validation
  - `ArrayValidatorTest.php` - Plain array validation
  - `StringValidatorTest.php` - String validation and formats
  - `IntValidatorTest.php` - Integer validation
  - `FloatValidatorTest.php` - Float validation
  - `FieldValidatorTest.php` - Base validator functionality
  - `NumericConstraintsTraitTest.php` - Trait-specific tests
- **Code Quality**: Achieved 100% PHPStan compliance and PHP-CS-Fixer standards
- **Performance**: Optimized validation logic and eliminated code duplication

## [0.2.0] - 2025-09-07

### Added

- `ObjectValidator` for validating `stdClass` objects, created with `Validator::isObject()`.
- Coercion support for `ObjectValidator` to convert associative arrays into `stdClass` objects.
- Coercion support for `AssociativeValidator` to convert `stdClass` objects into associative arrays.

### Changed

- **BREAKING**: Renamed `SchemaValidator` to `AssociativeValidator` for better API consistency.
- The factory method `Validator::isAssociative()` now returns an `AssociativeValidator` instance.
- Improved error message for associative array type validation to be more specific.

### Fixed

- Changed the type hint on `FieldValidator::tryValidate()` to `mixed` to correctly accept both array and object payloads as context.

## [0.1.0] - 2025-09-05

### Added

- Initial release of the `lemmon/validator` package.
