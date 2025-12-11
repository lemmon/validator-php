# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added
- `getFlattenedErrors()` method on `ValidationException` returning a flattened list of errors suitable for API consumption. Each error entry contains `path` (field path with `'_root'` for root-level errors) and `message` (error message). Supports nested structures, array items, and maintains consistent path notation.
- `ValidationException::flattenErrors()` static method for flattening errors from `tryValidate()` results without needing to catch exceptions. Returns empty array when passed `null`.

## [0.10.0] - 2025-12-10

### Added
- Numeric comparison helpers (`gt`, `gte`, `lt`, `lte`), non-negative/non-positive validators, and `clampToRange(min, max)` transformation for numeric validators.
- String format validators: `hostname()`, `time()`, `base64()`, `hex()`, `domain()`, and `regex()` alias method for `pattern()` on `StringValidator`.
- `port()` method for `IntValidator` to validate port numbers (1-65535) as numeric constraints.
- Enum-based variant flags: `IpVersion` enum for IP address validation, `Base64Variant` enum for Base64 validation, and `UuidVariant` enum for UUID validation.
- UUID version 7 support in `UuidVariant` enum (Unix timestamp-based, sortable).
- Array length constraints: `minItems()` and `maxItems()` methods for `ArrayValidator` to validate array size.
- Array contains validation: `contains()` method for `ArrayValidator` supporting both specific value matching and validator-based item matching.
- Comprehensive documentation clarifying library philosophy: focus on core validation principles over exhaustive validator coverage, with external libraries encouraged for advanced/specialized validators.

### Changed
- **BREAKING**: Refactored `ip()` method to use `IpVersion` enum flag instead of separate `ipv4()`/`ipv6()` methods. Use `ip(IpVersion::IPv4)` or `ip(IpVersion::IPv6)` for version-specific validation. Enum flag comes first, message parameter last.
- **BREAKING**: Refactored `base64()` method to use `Base64Variant` enum flag. Use `base64(Base64Variant::UrlSafe)` for URL-safe Base64 validation or `base64(Base64Variant::Any)` to accept both variants. Enum flag comes first, message parameter last.
- **BREAKING**: Refactored `uuid()` method to use `UuidVariant` enum flag. Use `uuid(UuidVariant::V1)` through `uuid(UuidVariant::V7)` for version-specific validation, or `uuid(UuidVariant::Any)` (default) to accept all versions (1-7). Enum flag comes first, message parameter last.
- Enhanced documentation to clarify library philosophy: primary focus on core validation principles rather than implementing every possible validator. External libraries are strongly encouraged for advanced, specialized, or frequently-evolving validators.
- Added `declare(strict_types=1);` across the codebase for stricter type enforcement and clearer errors.
- Replaced php-cs-fixer with Mago for linting/formatting; scripts now map `composer lint`/`composer fix` to Mago.

## [0.9.0] - 2025-11-18

### Added
- **Validator cloning**: Added `clone()` support to all validators for safe reuse of prebuilt pipelines without shared state. Pipelines are rebound to the cloned instance and nested schemas/item validators deep-clone to avoid cross-contamination between validators.

## [0.8.0] - 2025-11-06
### Changed
- Migrated all runtime classes to the `Lemmon\Validator` namespace and aligned documentation/examples with the new structure.

## [0.7.0] - 2025-10-19

### Added
- **Type-Safe Pipeline Architecture**: Enhanced internal architecture with `PipelineType` enum for improved developer experience
  - **IDE Autocomplete Support**: `PipelineType::VALIDATION` and `PipelineType::TRANSFORMATION` with full IDE integration
  - **Refactoring Safety**: No more magic strings - IDE handles all references automatically during refactoring
  - **Self-Documenting Code**: Clear intent with documented enum cases explaining validation vs transformation behavior
  - **Future Extensibility**: Easy to add new pipeline types (e.g., `CONDITIONAL`, `ASYNC`) with type safety
  - **Zero Runtime Cost**: Enums compile to same string values with no performance impact
  - **Perfect Static Analysis**: PHPStan level max compliance with proper type annotations
- **Smart Null Handling**: Revolutionary null handling system that makes validation intuitive and order-independent
  - **Intelligent Null Processing**: Validations automatically skip `null` values unless field is marked as `required()`
  - **Order Independence**: `->email()->required()` and `->required()->email()` both work identically with `null` inputs
  - **Transformation Consistency**: Transformations (`pipe()`, `transform()`, `nullifyEmpty()`) always execute, even on `null` values
  - **Global Required Flag**: `required()` works as a global flag (like `coerce()`), not a pipeline step
  - **Smart Default Application**: Default values applied after pipeline execution if final result is `null`
  - **Real-World Benefit**: Eliminates confusing execution order dependencies - write validation chains naturally
  - **Backward Compatible**: All existing code works unchanged with the full suite
- **Unified Pipeline Architecture**: Revolutionary single pipeline design that maintains conceptual clarity while optimizing execution
  - **Hybrid Execution Model**: Pure validations (like `email()`, `minLength()`) collect all errors for better UX, while transformations (like `required()`, `pipe()`) fail fast for correct behavior
  - **Execution Order Guarantee**: All methods execute in the exact order written in the fluent chain - `->email()->required()->pipe('trim')` works exactly as expected
  - **Conceptual Simplicity**: From developer perspective, there's one pipeline where callbacks do whatever they need (validate, transform, or both)
  - **Performance Optimized**: Error collection only where beneficial, fail-fast where appropriate
  - **Backward Compatible**: All existing code works unchanged across the full suite
  - **Internal Architecture**: Maintains separate `$validations` (error collection) and `$pipeline` (transformations) arrays for optimal execution
- **New `satisfies*` API**: Enhanced instance logical combinators for improved API consistency and flexibility
  - `satisfiesAny(array $validations, ?string $message = null)` - Validates that value passes ANY of the provided validators or callables
  - `satisfiesAll(array $validations, ?string $message = null)` - Validates that value passes ALL of the provided validators or callables
  - `satisfiesNone(array $validations, ?string $message = null)` - Validates that value satisfies NONE of the provided validators or callables
  - **Enhanced `satisfies()` Method**: Now accepts `callable|FieldValidator` instances for maximum flexibility
  - **Backward Compatibility**: Deprecated `anyOf()`, `allOf()`, `not()` instance methods maintained as aliases
  - **API Clarity**: Eliminates confusion between static `Validator::anyOf()` and instance `$validator->anyOf()` methods
  - Updated test metrics to reflect expanded coverage
- **Refactored `oneOf()` to `OneOfTrait`**: Improved type safety and execution order consistency
  - **Type Safety**: `oneOf()` now only available on primitive validators (`StringValidator`, `IntValidator`, `FloatValidator`, `BoolValidator`)
  - **Execution Order**: `oneOf()` now implemented as transformation, respecting fluent API chain position
  - **Logical Consistency**: Removed from complex types (`ArrayValidator`, `ObjectValidator`) where it doesn't make semantic sense
  - **Breaking Change**: `ArrayValidator` and `ObjectValidator` no longer have `oneOf()` method
  - Added comprehensive test coverage and removed invalid test cases
- **Form-Safe Empty String Coercion**: Enhanced ObjectValidator and AssociativeValidator coercion for better form handling
  - `ObjectValidator::coerce()`: Empty strings (`''`) now coerce to empty `stdClass` objects
  - `AssociativeValidator::coerce()`: Empty strings (`''`) now coerce to empty arrays `[]`
  - **Real-World Benefit**: Form parameters like `?settings=` now create empty structures instead of failing validation
  - **Type Safety Maintained**: Non-empty strings still fail validation as expected
  - Added comprehensive test coverage
- **Enhanced `required()` Method**: Added optional custom error message parameter for consistency with other validation methods
  - `required(?string $message = null)` - Custom error messages for required field validation
  - **API Consistency**: All validation methods now support optional custom error messages
  - **Better UX**: Context-specific error messages for different forms and use cases
  - Maintains backward compatibility with existing code
- **Internal API Modernization**: Complete migration from deprecated `addValidation()` to `satisfies()` throughout codebase
  - **StringValidator**: All 10 validation methods (`email()`, `url()`, `uuid()`, `ip()`, `minLength()`, `maxLength()`, `length()`, `pattern()`, `datetime()`, `date()`) now use `satisfies()` internally
  - **NumericConstraintsTrait**: All 5 constraint methods (`min()`, `max()`, `multipleOf()`, `positive()`, `negative()`) now use `satisfies()` internally
  - **Static Methods**: `Validator::anyOf()`, `Validator::allOf()`, `Validator::not()` now use new `satisfies*` API internally
  - **Future-Proofing**: All internal code ready for `addValidation()` removal in v1.0.0
  - **Consistency**: Single source of truth for custom validation logic across entire codebase
  - Maintains perfect backward compatibility for users still using deprecated methods

### Fixed
- **CRITICAL BUG**: Fixed execution order for `required()` and `nullifyEmpty()` methods to respect fluent API contract
  - **Issue**: `Validator::isString()->pipe('trim')->nullifyEmpty()->required()->validate('    ')` incorrectly passed validation
  - **Root Cause**: `required()` and `nullifyEmpty()` executed before transformations, violating principle of least surprise
  - **Fix**: Moved both methods to transformation pipeline to respect their position in the fluent chain
  - **Impact**: Fluent API now works exactly as written - transformations execute in the order they appear
  - **Breaking Change**: Execution order now matches developer expectations (trim → nullifyEmpty → required)
  - **Real-World Benefit**: Form validation with trimming and empty string handling now works correctly
- **CRITICAL BUG**: Fixed floating-point precision issue in `multipleOf()` validation
  - **Issue**: `Validator::isFloat()->multipleOf(0.01)->validate(500.01)` failed due to floating-point arithmetic precision
  - **Root Cause**: `fmod(500.01, 0.01)` returned `0.009999999999980497` instead of `0.0`
  - **Fix**: Implemented epsilon comparison (`1e-9` tolerance) for floating-point remainder validation
  - **Impact**: Decimal multiples (currency, measurements) now validate correctly
  - Added comprehensive test coverage for floating-point precision edge cases
- **CRITICAL BUG**: Fixed schema validation field inclusion behavior in ObjectValidator and AssociativeValidator
  - **Issue**: Validators were incorrectly including ALL schema fields in results, even when not provided in input
  - **Fix**: Now only includes fields that were actually provided in input OR have default values applied
  - **Impact**: Results now accurately reflect validated data without unexpected properties
  - **Behavior**: Required field validation still works correctly (missing required fields still fail)
  - Added comprehensive test coverage to prevent regression
  - **Documentation**: Added "Field Inclusion Behavior" section to Object Validation guide with clear examples

### Documentation
- **Complete type-aware transformation system documentation** - Added comprehensive coverage for `transform()` and `pipe()` methods
  - Added "Data Transformations" section to Core Concepts guide with detailed type context switching explanation
  - Added transformation examples to Basic Usage guide with clear usage guidelines
  - Documented `filterEmpty()` method for ArrayValidator with practical examples and real-world use cases
  - Added "Type-Specific Methods" section to API Reference with complete array method documentation
  - Fixed API reference to match actual implementation (removed unimplemented boolean parameter methods)
  - Updated ROADMAP to remove unimplemented enhanced methods and focus on existing features
- **Complete `nullifyEmpty()` documentation** - Added comprehensive coverage across all guides and API reference
  - Added "Universal Methods" section to API Reference with detailed `nullifyEmpty()` documentation
  - Added "Form-Safe String Handling" section to String Validation Guide with practical examples
  - Added "Explicit Empty String Nullification" section to Numeric Validation Guide
  - Added "Form-Safe Validation with nullifyEmpty()" section to Form Validation Examples
  - Added "Empty String Nullification" section to Basic Usage Guide with fundamental patterns
  - Updated README.md Quick Start example to showcase `nullifyEmpty()` usage
- **Fixed all dead links in documentation** - Replaced 15 dead links with existing comprehensive guides
  - Fixed missing API reference links across all documentation files
  - Ensured all internal navigation works properly for seamless user experience
- **Added development notice** - Clear indication that the library is in active development with potential API changes

## [0.6.0] - 2025-10-09

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
  - Added comprehensive test coverage for new behavior

### Added
- **New `satisfies()` Method**: Intuitive custom validation with optional error messages
  - Replaces verbose `addValidation()` with natural language: `->satisfies(fn($v) => $v > 0)`
  - Optional error message parameter with sensible default: "Custom validation failed"
  - Maintains backward compatibility with deprecated `addValidation()` method
  - Added comprehensive test coverage
  - Updated all documentation (65+ references) to use new method
- **BoolValidator Test Suite**: Complete test coverage for boolean validation and coercion
  - Tests for string boolean coercion (`'true'`, `'false'`, `'on'`, `'off'`, `'1'`, `'0'`)
  - Case-insensitive coercion support
  - Empty string safety validation

### Improved
- **Developer Experience**: Natural language validation with `satisfies()` method reads like English
- **API Consistency**: Optional error messages eliminate forced message requirements
- **Documentation Quality**: Comprehensive updates across all guides with practical examples
- **Form Safety**: Real-world protection against dangerous zero defaults in financial and quantity fields

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
  - **Test Coverage**: Expanded coverage across numeric, array, and type-aware transformation validators

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
  - **Comprehensive Test Suite**: Expanded coverage for static logical combinators

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
