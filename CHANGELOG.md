# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Fixed
- **CRITICAL BUG**: Fixed ObjectValidator null property handling - `isset()` was excluding null properties from result objects
  - ObjectValidator now correctly includes all validated properties in the result, even when they are null
  - Maintains consistency with AssociativeValidator behavior
  - Added comprehensive test coverage to prevent regression

### Changed
- **BREAKING CHANGE**: Enhanced form safety for empty string coercion across all validators
  - `IntValidator::coerce()`: Empty strings (`''`) now convert to `null` instead of `0`
  - `FloatValidator::coerce()`: Empty strings (`''`) now convert to `null` instead of `0.0`
  - `BoolValidator::coerce()`: Empty strings (`''`) now convert to `null` instead of validation failure
  - **Rationale**: Prevents dangerous zero defaults in form fields (e.g., bank balances, quantities)
  - **Migration**: Use explicit `->default(0)` if you need zero defaults for empty form fields
  - Added comprehensive test coverage for new behavior (110 tests, 278 assertions)

### Added
- **BoolValidator Test Suite**: Complete test coverage for boolean validation and coercion
  - Tests for string boolean coercion (`'true'`, `'false'`, `'on'`, `'off'`, `'1'`, `'0'`)
  - Case-insensitive coercion support
  - Empty string safety validation

## [0.5.0] - 2025-10-08

### Added
- **Enhanced Numeric Coercion**: `IntValidator` and `FloatValidator` coercion improvements for form handling
  - ⚠️ **SUPERSEDED**: This behavior was changed in the next release for safety reasons
  - See "Unreleased" section for current empty string coercion behavior
- **Array Filtering with Auto-Reindexing**: New `filterEmpty()` method for `ArrayValidator`
  - Removes empty strings (`''`) and `null` values while preserving valid falsy values (`0`, `false`, `[]`)
  - Automatically reindexes arrays to maintain indexed array structure (0, 1, 2, 3...)
  - Works seamlessly with item validators for comprehensive array cleanup
- **Type-Aware Transformation System**: Revolutionary transformation infrastructure with intelligent type handling
  - `transform(callable $transformer)` - Type-flexible transformations that can change data types
  - `pipe(callable ...$transformers)` - Type-preserving transformations that maintain current type context
  - **Dynamic Type Context**: Automatically tracks and switches type context during transformation chains
  - **Smart Array Coercion**: `pipe()` operations on arrays automatically reindex to maintain structure
  - **Associative Key Preservation**: AssociativeValidator preserves keys during `pipe()` operations
  - Enables complex multi-type transformation chains (Array → String → Int) with intuitive syntax

### Improved
- **Form Data Handling**: Enhanced coercion makes form validation significantly more practical and intuitive
- **Array Data Integrity**: `filterEmpty()` maintains ArrayValidator's contract of returning properly indexed arrays
- **Extensibility**: Universal transformation methods enable unlimited flexibility with external library integration
- **Test Coverage**: Added 27 new tests across numeric, array, and type-aware transformation validators (103 total tests, 250 assertions)

## [0.4.0] - 2025-10-06

### Added
- **Static Logical Combinators**: New static factory methods for advanced validation logic
  - `Validator::anyOf(array $validators)` - Creates validator that passes if ANY validator passes (perfect for mixed-type validation)
  - `Validator::allOf(array $validators)` - Creates validator that passes if ALL validators pass (ideal for combining constraints)
  - `Validator::not(FieldValidator $validator)` - Creates validator that passes if the provided validator does NOT pass (exclusion logic)
- **Enhanced Mixed-Type Support**: Clean syntax for validating arrays with mixed item types
- **Comprehensive Documentation**: Complete documentation restructure with focused guides, API reference, and practical examples
  - Getting Started guides (Installation, Basic Usage, Core Concepts)
  - Validation Guides (String, Numeric, Array, Object, Custom, Error Handling)
  - API Reference with complete method documentation including new static combinators
  - Real-world Examples (Form validation, API validation, E-commerce)
- **Enhanced README**: Concise overview with quick start examples and organized navigation
- **Comprehensive Test Suite**: Added 19 new tests (76 total) with 54 new assertions (208 total) for static logical combinators

### Improved
- **API Consistency**: Static logical combinators provide cleaner syntax than instance-only methods
- **Mixed-Type Validation**: Simplified array validation with `Validator::anyOf()` for mixed types
- **Documentation Coverage**: All new methods fully documented with practical examples
- **Type Safety**: Static combinators work with any data type while maintaining type safety

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
