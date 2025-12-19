# Lemmon Validator - Development Roadmap

This roadmap outlines the strategic development plan for future releases, prioritizing features that deliver maximum value to the PHP validation ecosystem.

## Strategic Philosophy

**Extensibility Over Reinvention:** We focus on creating a clean, extensible validation flow rather than reimplementing existing libraries. Our `transform()` method enables seamless integration with established libraries like Laravel Str, Symfony String, and Laravel Collections. This approach keeps our library lightweight while providing unlimited flexibility through the rich PHP ecosystem.

**Core Principle:** Validate first, transform with the best tools available.

## Recently Completed

### Flattened Errors for API Consumption (Current)
- [x] **Flattened Error Response** - Added `getFlattenedErrors()` method on `ValidationException` returning API-friendly error format
- [x] **Static Flatten Method** - Added `ValidationException::flattenErrors()` static method for use with `tryValidate()` results
- [x] **Path Convention** - Implemented `'_root'` namespace for root-level errors, dot notation for nested fields, index notation for arrays
- [x] **Comprehensive Documentation** - Added complete guide section with examples for both exception and tuple-based patterns
- [x] **Full Test Coverage** - Added 15 test cases covering all validation scenarios and edge cases

### String Validators Bundle & Array Enhancements
- [x] **String Format Validators** - Added `hostname()`, `time()`, `base64()`, `hex()`, `domain()`, and `regex()` alias
- [x] **Enum-Based Variant Flags** - Implemented `IpVersion`, `Base64Variant`, and `UuidVariant` enums for type-safe variant selection
- [x] **UUID v7 Support** - Added support for UUID version 7 (Unix timestamp-based, sortable)
- [x] **API Consistency** - Established consistent pattern: enum flags come first, message parameter last
- [x] **Port Validator** - Moved `port()` to `IntValidator` as numeric constraint (1-65535)
- [x] **Array Length Constraints** - Added `minItems()` and `maxItems()` for array size validation
- [x] **Array Contains Validation** - Added `contains()` supporting value matching and validator-based item matching
- [x] **Documentation Philosophy** - Clarified library's focus on core validation principles over exhaustive validator coverage

### Type-Safe Pipeline Architecture
- [x] **PipelineType Enum Implementation** - Modern PHP 8.1+ enum for type-safe pipeline operations
- [x] **IDE Integration** - Full autocomplete support for `PipelineType::VALIDATION` and `PipelineType::TRANSFORMATION`
- [x] **Refactoring Safety** - No more magic strings, IDE handles all references automatically
- [x] **Self-Documenting Code** - Enum cases include comprehensive documentation explaining validation vs transformation behavior
- [x] **Perfect Static Analysis** - PHPStan level max compliance with proper type annotations
- [x] **Zero Performance Cost** - Enums compile to identical string values with no runtime overhead

### Smart Null Handling (v0.7.0)
- [x] **Revolutionary Null Handling System** - Intuitive, order-independent validation behavior
- [x] **Intelligent Null Processing** - Validations skip `null` unless `required()`, transformations always execute
- [x] **Order Independence** - `->email()->required()` and `->required()->email()` work identically
- [x] **Global Required Flag** - `required()` works as a flag (like `coerce()`), not a pipeline step
- [x] **Smart Default Application** - Defaults applied after pipeline execution if final result is `null`
- [x] **Real-World Safety** - Eliminates confusing execution order dependencies in production

### Unified Pipeline Architecture (v0.6.0)
- [x] **Revolutionary Single Pipeline Design** - Conceptual simplicity with hybrid execution model for optimal performance
- [x] **Execution Order Guarantee** - All methods execute in the exact order written in fluent chains
- [x] **Hybrid Execution Model** - Error collection for pure validations (better UX), fail-fast for transformations (correct behavior)
- [x] **Backward Compatibility** - All existing code works unchanged with the full suite
- [x] **Performance Optimized** - No unnecessary error collection overhead for transformation operations

### Static Logical Combinators (v0.4.0)
- [x] **Static Logical Combinators** - `Validator::anyOf()`, `Validator::allOf()`, `Validator::not()` for type-agnostic validation
- [x] **Enhanced Mixed-Type Support** - Clean syntax for arrays with mixed item types
- [x] **Comprehensive Documentation** - Complete API reference and practical examples
- [x] **Comprehensive Test Suite** - Expanded coverage for the new combinators

## Immediate Tasks

### Critical Documentation Updates
- [x] **Document `nullifyEmpty()` method** - Add to README, API reference, string/form guides (currently only in array guide)
  - Added comprehensive "Universal Methods" section to API Reference with detailed `nullifyEmpty()` documentation
  - Added "Form-Safe String Handling" section to String Validation Guide with practical examples
  - Added "Explicit Empty String Nullification" section to Numeric Validation Guide
  - Updated README.md Quick Start example to showcase `nullifyEmpty()` usage
- [x] **Add form handling examples** - Show `nullifyEmpty()` in real-world form validation scenarios
  - Added comprehensive "Form-Safe Validation with nullifyEmpty()" section to Form Validation Examples
  - Included real-world ProductFormValidator example demonstrating form-safe patterns
  - Added contact form example updates showing proper `nullifyEmpty()` usage
- [x] **Update getting started guides** - Include `nullifyEmpty()` in basic usage patterns
  - Added "Empty String Nullification" section to Basic Usage Guide with fundamental patterns
  - Included practical examples combining `nullifyEmpty()` with `default()` for optional fields
  - Added guidelines on when to use `nullifyEmpty()` for different scenarios
- [x] **Fix all dead links in documentation** - Replace missing API references with existing guides
  - Fixed 15 dead links across all documentation files
  - Replaced missing API reference links with existing comprehensive guides
  - Ensured all internal navigation works properly

### Critical API Improvements
- [x] **Create `clone()` function** - Provide a first-class way to duplicate validator pipelines for reuse without rebuilding chains manually
  - Added deep clone support with pipeline closure rebinding to cloned instances
  - Nested schemas/item validators now clone recursively to prevent shared state
  - Added regression tests covering pipeline state isolation and schema deep copying
- [x] **Rename `addValidation()` to `satisfies()`** - Intuitive, descriptive method name that clearly indicates validation intent
  - Added new `satisfies(callable $validation, ?string $message = null)` method with optional error message
  - Maintains backward compatibility with deprecated `addValidation()` method
  - Provides generic fallback message "Custom validation failed" when no message provided
  - Added comprehensive test coverage
  - Method reads naturally: `->satisfies(fn($v) => $v > 0)` - "value must satisfy this condition"
- [x] **Update all documentation** - Replace `addValidation()` with `satisfies()` across all guides and examples
  - Updated Custom Validation Guide (primary guide for this method)
  - Updated Form Validation Examples (22+ references)
  - Updated Core Concepts, Basic Usage, and API Reference guides
  - Updated Error Handling, Numeric, String, and Object Validation guides
  - Maintained backward compatibility with deprecated `addValidation()` method
- [x] **Complete internal API modernization** - Migrate all internal code from `addValidation()` to `satisfies()`
  - Migrated all StringValidator methods (10 methods) to use `satisfies()` internally
  - Migrated all NumericConstraintsTrait methods (5 methods) to use `satisfies()` internally
  - Updated static logical combinators to use new `satisfies*` API internally
  - Codebase now fully consistent with modern API while maintaining backward compatibility

### Critical Bug Fixes
- [x] **Fix ObjectValidator null property handling** - `isset($validatedFieldValue)` excluded null properties from result object
  - Fixed: ObjectValidator now correctly includes all validated properties, even when null
  - Maintains consistency with AssociativeValidator behavior
  - Added comprehensive test coverage to prevent regression
- [x] **Fix schema validation field inclusion behavior** - ObjectValidator and AssociativeValidator were including ALL schema fields in results
  - **Issue**: Validators incorrectly added all schema properties to results, even when not provided in input
  - **Fix**: Now only includes fields that were actually provided in input OR have default values applied
  - **Impact**: Results accurately reflect validated data without unexpected properties
  - **Behavior**: Required field validation still works correctly (missing required fields still fail)
  - Added comprehensive test coverage to prevent regression
- [x] **Fix dangerous empty string coercion across all validators** - **BREAKING CHANGE**: `coerce()` now converts `''` → `null` for form safety
  - IntValidator: `''` → `null` (not 0) - prevents dangerous zero defaults in forms
  - FloatValidator: `''` → `null` (not 0.0) - prevents dangerous zero defaults in forms
  - BoolValidator: `''` → `null` (not false) - empty query params should be null
  - Critical for real-world form/API safety (prevents accidental zero bank balances, etc.)
- [x] **Add comprehensive tests for empty string handling** - Added BoolValidator test suite and updated existing tests
  - Complete coverage for new empty string → null coercion behavior
- [x] **Update documentation** - Explain the form-safety rationale behind empty string → null coercion
  - Added comprehensive form-safety sections to Core Concepts and Numeric Validation guides
  - Included real-world examples showing dangerous scenarios prevented
  - Added migration guidance for users who need explicit zero defaults
  - Added brief form-safety note in Basic Usage guide with cross-references

## Next Release -- Utility Features

### String Enhancements
- [x] **`hostname()`** -- Validates hostname format (domain names, subdomains)
- [x] **`time()`** -- Validates time format (HH:MM:SS, HH:MM)
- [x] **`base64()`** -- Validates Base64 encoded strings with `Base64Variant` enum (Standard, UrlSafe, Any)
- [x] **`hex()`** -- Validates hexadecimal strings (simple regex, optional length constraints)
- [x] **`regex()` alias** -- Alternative name for `pattern()` method for clarity
- [x] **`domain()`** -- Validates domain names (stricter than hostname, requires at least one dot)
- [x] **Enum-based variant flags** -- `IpVersion`, `Base64Variant`, and `UuidVariant` enums for type-safe variant selection
- [x] **UUID v7 support** -- Added support for UUID version 7 (Unix timestamp-based, sortable)

### String Transformations
- [x] **`transform()`** - Single transformation method (already implemented!)
- [x] **`pipe(...$transformers)`** - Multiple transformations in sequence with variadic arguments (already implemented!)
- [x] **`nullifyEmpty()`** - Convert empty strings/arrays to null (already implemented!)

### Common Normalization Patterns
```php
// Empty-to-null conversions (very common in forms):
->transform(fn($v) => $v === '' ? null : $v)           // Empty string to null
->transform(fn($v) => empty($v) ? null : $v)           // Empty array/string to null

// String normalizations:
->transform(fn($v) => $v === 'null' ? null : $v)       // String "null" to actual null
->transform(fn($v) => $v === '0' ? 0 : $v)             // String "0" to integer 0
->transform(fn($v) => $v === 'false' ? false : $v)     // String "false" to boolean false

// Numeric normalizations:
->transform(fn($v) => $v === '' ? 0 : $v)              // Empty string to zero
->transform(fn($v) => is_string($v) ? (float)$v : $v)  // String numbers to float

// Array normalizations:
->transform(fn($v) => array_filter($v))                // Remove empty values
->transform(fn($v) => array_values($v))                // Re-index array
```

### Removed from Scope

**General Principle:** The library's primary focus is on core validation principles (type safety, fluent APIs, error handling, extensibility) rather than implementing every possible validator. For advanced, specialized, or frequently-evolving validators, leveraging external libraries via `satisfies()` is strongly encouraged. This keeps the library focused on its core strengths while enabling you to leverage domain expertise from specialized libraries.

**Removed Validators:**
- ~~`trim()`~~ -- Use `->transform('trim')` or `->transform(fn($v) => trim($v))` for predictability
- ~~`toCamelCase()` / `toSnakeCase()`~~ -- Better handled by specialized libraries (Laravel Str, Symfony String)
- ~~`slugify()`~~ -- Complex internationalization logic, use dedicated libraries
- ~~`normalizeSpaces()`~~ -- Complex whitespace handling (spaces, tabs, newlines, Unicode), use `Str::squish()`
- ~~`cuid2()` / `nanoid()` / `ulid()`~~ -- Use specialized libraries (ramsey/uuid, hidehalo/nanoid-php, ulid/php)

**Note on UUID:** UUID validation is provided as a built-in convenience method due to its widespread use. However, external libraries are encouraged because they stay current with newer UUID variants and versions, while the built-in implementation may fall behind as the library prioritizes core validation principles over exhaustive validator coverage. For advanced UUID features (parsing, generation, strict RFC compliance, latest specifications), using [`ramsey/uuid`](https://github.com/ramsey/uuid) via `satisfies()` is recommended.

### Extensibility Examples
```php
// Single transformations (explicit and clear):
$validator = Validator::isString()
    ->minLength(3)
    ->transform('trim')                         // Single transformation
    ->transform(fn($v) => Str::squish($v))      // Another single transformation
    ->transform(fn($v) => Str::slug($v));       // Laravel: create URL slug

// Pipeline transformations (clean variadic syntax):
$textValidator = Validator::isString()
    ->required()
    ->pipe(                                    // Multiple transformations -- no array brackets!
        'trim',
        'mb_strtolower',
        fn($v) => Str::title($v),
        fn($v) => Str::limit($v, 50)
    );

// Mixed approach (semantic clarity):
$mixedValidator = Validator::isString()
    ->transform('trim')                        // Important single step
    ->pipe('strtolower', fn($v) => Str::title($v)) // Clean batch operations
    ->transform(fn($v) => Str::slug($v));      // Final transformation

// Dynamic pipeline building:
$transformers = ['trim', 'strtolower'];
$dynamicValidator = Validator::isString()
    ->pipe(...$transformers);                  // Spread operator for dynamic arrays

// Modern identifier validation with specialized libraries:
use Ramsey\Uuid\Uuid;
use Hidehalo\Nanoid\Client as NanoidClient;

$uuidValidator = Validator::isString()
    ->satisfies(fn($v) => Uuid::isValid($v), 'Must be valid UUID');

$nanoidValidator = Validator::isString()
    ->satisfies(fn($v) => (new NanoidClient())->isValid($v), 'Must be valid Nano ID');

// Real-world form data normalization (with dedicated methods):
$formValidator = Validator::isAssociative([
    'name' => Validator::isString()
        ->nullifyEmpty()                                // Already implemented!
        ->transform('trim')
        ->required(),
    'age' => Validator::isInt()
        ->nullifyEmpty()                                // Works across all types
        ->coerce(),
    'salary' => Validator::isFloat()
        ->coerce(),                                     // Enhanced: empty strings → null
    'tags' => Validator::isArray()
        ->filterEmpty()                                 // Remove empty values + auto-reindex
        ->default([])
]);

// Compare: Before vs After
// Before (verbose):
->transform(fn($v) => $v === '' ? null : $v)            // Empty string to null
->transform(fn($v) => array_filter($v))                 // Remove empty values
->transform(fn($v) => array_values($v))                 // Re-index array

// After (clean and discoverable):
->coerce()                                              // Enhanced: empty strings → null (form-safe)!
->filterEmpty()                                         // Clean array method + auto-reindex!

// Type-aware transformation chains (NEW!):
$result = Validator::isArray()
    ->pipe('array_unique', 'array_reverse')        // Array operations (auto-reindexed)
    ->transform(fn($v) => implode(',', $v))        // Array → String (type switch)
    ->pipe('trim', 'strtoupper')                   // String operations
    ->transform('strlen')                          // String → Int (type switch)
    ->validate(['a', 'b', 'a']); // Returns: 3

// Complex array processing with existing tools:
$arrayValidator = Validator::isArray()
    ->minItems(1)
    ->transform(fn($v) => collect($v)->unique()->values()->all()); // Laravel Collections

// String processing with Symfony:
$stringValidator = Validator::isString()
    ->email()
    ->transform(fn($v) => u($v)->lower()->toString()); // Symfony String
```

### Array Enhancements
- [x] **`minItems()`** / **`maxItems()`** - Array length constraints
- [x] **`contains()`** - Validates array contains specific item or item matching validator

### Array Transformations
- [x] **`transform()`** / **`pipe()`** - Generic transformation methods (already implemented!)
- [x] **`nullifyEmpty()`** - Convert empty arrays to null (already implemented!)
- [x] **`filterEmpty()`** - Remove empty/null values and reindex automatically (already implemented!)

### Removed from Scope
- ~~`unique()`~~ -- Use `array_unique()` or Laravel Collections (complex deduplication logic)
- ~~`flatten()`~~ -- Complex recursive logic, use Laravel Collections or `array_merge_recursive()`

### Numeric Enhancements
- [x] **`nonNegative()`** - Validates numbers >= 0 (includes zero)
- [x] **`nonPositive()`** - Validates numbers <= 0 (includes zero)
- [x] **`gt()`** / **`gte()`** -- Greater than / greater than or equal explicit comparisons
- [x] **`lt()`** / **`lte()`** -- Less than / less than or equal explicit comparisons

### Numeric Transformations
- [x] **`clampToRange(min, max)`** - Restrict numbers to range (not obvious: max(min, min(max, value)))
- [x] **Enhanced `coerce()`** - Empty strings → `null` for form safety (BREAKING CHANGE implemented!)
- [x] **`nullifyEmpty()`** - Convert empty strings to null (already implemented!)
- [x] **`transform()`** / **`pipe()`** - Generic transformation methods (already implemented!)

### Removed from Scope
- ~~`round(precision)`~~ -- Use `->pipe(fn($v) => round($v, $precision))` for clarity

### Universal Validators
- [ ] **`enum()`** - Validates value is one of predefined options (available on all validators)
- [ ] **`const()`** - Validates value equals specific constant

### Universal Transformations
- [x] **`transform()`** / **`pipe()`** - Generic transformation methods (already implemented on all validators!)

### Removed from Scope
- ~~`when(condition, transform)`~~ -- Complex conditional logic, use external control flow

### Enhanced Error Handling
- [x] **Error path enhancement** - Full field paths for nested validation errors (flattened errors with `'_root'` namespace)
- [ ] **Structured error codes** - Programmatic error identification

### Real-World Validation Gaps (For Consideration)
- [ ] **`notEmpty()` method** - Explicit validation that value is not empty string/array (clearer than custom validation)
- [ ] **`in()` alias for `oneOf()`** - More intuitive method name (`->in(['active', 'inactive'])`)
- [ ] **`between(min, max)` for strings** - Length validation shorthand (`->between(3, 50)` instead of `->minLength(3)->maxLength(50)`)
- [ ] **`between(min, max)` for numerics** - Range validation shorthand (`->between(1, 100)` instead of `->min(1)->max(100)`)
- [ ] **`filled()` method** - Requires non-null AND non-empty (stricter than required())
- [ ] **`when()` conditional validation** - Apply validation only when condition is met (`->when($userRole === 'admin', fn($v) => $v->required())`)

### Probably Redundant (Default Behavior)
- [ ] ~~`optional()` method~~ - Redundant since everything is optional by default
- [ ] ~~`nullable()` method~~ - Redundant since everything accepts null by default (unless required)

## Future Release - Advanced Schema Features

### Schema Composition
- [ ] **`additionalProperties()`** - Control undefined properties in schemas
- [ ] **`passthrough()`** - Preserve unknown keys/properties in schema output (opt-in, default remains schema-only)
- [ ] **`patternProperties()`** - Validate properties matching regex patterns
- [ ] **`propertyNames()`** - Validate property names themselves
- [ ] **`dependencies()`** - Conditional validation based on other properties

### Schema Manipulation
- [ ] **`partial()`** - Make all schema fields optional
- [ ] **`pick()`** - Create schema with only specified fields
- [ ] **`omit()`** - Create schema excluding specified fields
- [ ] **`merge()`** - Combine multiple schemas

### Advanced Array Features
- [ ] **`additionalItems()`** - Control validation of extra array items
- [ ] **Tuple validation** - Fixed-position array item validation

## Long-term Vision

### Performance & Scalability
- [ ] **Validation caching** - Cache expensive validation results
- [ ] **Lazy evaluation** - Optimize validation chains
- [ ] **Parallel validation** - Concurrent validation for independent rules

### Developer Experience
- [ ] **Schema serialization** - Export/import schemas as JSON
- [ ] **TypeScript definitions** - Generate TS types from PHP schemas
- [ ] **IDE integration** - Enhanced autocomplete and validation

### Framework Integration
- [ ] **Laravel integration** - Native Laravel validator bridge
- [ ] **Symfony integration** - Symfony Form component compatibility
- [ ] **PSR-7 middleware** - HTTP request validation middleware

## Ongoing Quality Initiatives

### Documentation
- [x] Comprehensive guide restructure
- [x] API reference documentation
- [x] Real-world examples
- [x] Complete `nullifyEmpty()` documentation across all guides
- [x] Form-safe validation examples and rationale
- [x] Fixed all dead links in documentation
- [ ] **Video tutorials** - Getting started screencasts
- [ ] **Interactive documentation** - Runnable examples

### Testing & Quality
- [x] Organized test suite with focused files
- [x] 100% PHPStan compliance
- [x] Mago lint/formatting standards
- [x] Static logical combinators test coverage
- [x] Comprehensive schema validation test coverage
- [x] Floating-point precision bug fixes with comprehensive edge case testing
- [ ] **Mutation testing** - Enhanced test quality verification
- [ ] **Property-based testing** - Randomized validation testing
- [ ] **Performance benchmarking** - Continuous performance monitoring

### Community & Ecosystem
- [ ] **Plugin architecture** - Third-party validator extensions
- [ ] **Community validators** - Shared validation library
- [ ] **Migration tools** - Automated upgrades between versions

## Development Process

### Feature Development Workflow
1. **Research & Design** - Community input, API design
2. **Implementation** - Core functionality with tests
3. **Documentation** - Comprehensive guides and examples
4. **Review & Testing** - Code review, edge case testing
5. **Release & Feedback** - Version release, community feedback

### Quality Gates
- **100% test coverage** for new features
- **PHPStan level max** compliance
- **Backward compatibility** preservation
- **Performance benchmarking** - No regressions
- **Documentation completeness** - Guides and examples
- **Ecosystem integration** - Prefer extensibility over reimplementation

### Release Cadence
- **Major releases** (x.0.0) - Breaking changes, major features (quarterly)
- **Minor releases** (x.y.0) - New features, enhancements (monthly)
- **Patch releases** (x.y.z) - Bug fixes, documentation (as needed)

## Success Metrics

### Adoption Metrics
- **Package downloads** - Packagist installation statistics
- **GitHub stars** - Community interest and engagement
- **Issue resolution** - Response time and resolution rate

### Quality Metrics
- **Test coverage** - Maintain >95% code coverage
- **Static analysis** - Zero PHPStan errors at max level
- **Performance** - Validation speed benchmarks
- **Documentation** - Comprehensive coverage of all features

### Community Health
- **Contribution guidelines** - Clear contributor onboarding
- **Code of conduct** - Inclusive community standards
- **Regular releases** - Consistent development momentum

---

This roadmap is reviewed quarterly and updated based on community feedback, usage patterns, and emerging PHP ecosystem trends. Feature priorities may be adjusted based on user demand and contribution availability.
