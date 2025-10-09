# Lemmon Validator - Development Roadmap

This roadmap outlines the strategic development plan for future releases, prioritizing features that deliver maximum value to the PHP validation ecosystem.

## 🎯 **Strategic Philosophy**

**Extensibility Over Reinvention:** We focus on creating a clean, extensible validation flow rather than reimplementing existing libraries. Our `transform()` method enables seamless integration with established libraries like Laravel Str, Symfony String, and Laravel Collections. This approach keeps our library lightweight while providing unlimited flexibility through the rich PHP ecosystem.

**Core Principle:** Validate first, transform with the best tools available.

## ✅ Recently Completed (v0.4.0) - Static Logical Combinators

### Advanced Validation Logic
- [x] ✅ **Static Logical Combinators** - `Validator::anyOf()`, `Validator::allOf()`, `Validator::not()` for type-agnostic validation
- [x] ✅ **Enhanced Mixed-Type Support** - Clean syntax for arrays with mixed item types
- [x] ✅ **Comprehensive Documentation** - Complete API reference and practical examples
- [x] ✅ **Comprehensive Test Suite** - 19 new tests (76 total) with 54 new assertions (208 total)

## 📋 Immediate Tasks (Documentation Gaps from Real-World Testing)

### Critical Documentation Updates
- [ ] **Document `nullifyEmpty()` method** - Add to README, API reference, string/form guides (currently only in array guide)
- [ ] **Add form handling examples** - Show `nullifyEmpty()` in real-world form validation scenarios
- [ ] **Update getting started guides** - Include `nullifyEmpty()` in basic usage patterns

### Critical API Improvements
- [ ] **Rename `addValidation()` to `validate()`** - Shorter, cleaner method name consistent with `transform()` pattern
- [ ] **Make error message optional in `validate()`** - Add generic fallback message for better developer experience
- [ ] **Update all documentation** - Replace `addValidation()` with `validate()` across all guides and examples

### Critical Bug Fixes
- [ ] **Fix ObjectValidator null property handling** - `isset($validatedFieldValue)` excludes null properties from result object
  - Should return object with null properties, not empty object
  - AssociativeValidator works correctly, ObjectValidator has the bug
- [ ] **Fix dangerous empty string coercion across all validators** - Make `coerce()` convert `''` → `null` (not 0/false) for form safety
  - IntValidator: `''` → `null` (not 0) - prevents dangerous zero defaults in forms
  - FloatValidator: `''` → `null` (not 0.0) - prevents dangerous zero defaults in forms
  - BoolValidator: `''` → `null` (not false) - empty query params should be null
  - This is a **BREAKING CHANGE** but critical for real-world form/API safety
- [ ] **Add comprehensive tests for empty string handling** - Ensure all validators treat empty strings as "no value provided"
- [ ] **Update documentation** - Explain the form-safety rationale behind empty string → null coercion

## 📋 Next Release (v0.5.0) - Utility Features

### String Enhancements
- [ ] **`hostname()`** - Validates hostname format (domain names, subdomains)
- [ ] **`time()`** - Validates time format (HH:MM:SS, HH:MM)
- [ ] **`base64()`** - Validates Base64 encoded strings (simple regex, no dependencies)
- [ ] **`hex()`** - Validates hexadecimal strings (simple regex, optional length constraints)
- [ ] **`regex()` alias** - Alternative name for `pattern()` method for clarity

### String Transformations
- [x] ✅ **`transform()`** - Single transformation method (already implemented!)
- [x] ✅ **`pipe(...$transformers)`** - Multiple transformations in sequence with variadic arguments (already implemented!)
- [x] ✅ **`nullifyEmpty()`** - Convert empty strings/arrays to null (already implemented!)

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
- ~~`trim()`~~ - Use `->transform('trim')` or `->transform(fn($v) => trim($v))` for predictability
- ~~`toCamelCase()` / `toSnakeCase()`~~ - Better handled by specialized libraries (Laravel Str, Symfony String)
- ~~`slugify()`~~ - Complex internationalization logic, use dedicated libraries
- ~~`normalizeSpaces()`~~ - Complex whitespace handling (spaces, tabs, newlines, Unicode), use `Str::squish()`
- ~~`cuid2()` / `nanoid()` / `ulid()`~~ - Use specialized libraries (ramsey/uuid, hidehalo/nanoid-php, ulid/php)

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
    ->pipe(                                    // Multiple transformations - no array brackets!
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
    ->addValidation(fn($v) => Uuid::isValid($v), 'Must be valid UUID');

$nanoidValidator = Validator::isString()
    ->addValidation(fn($v) => (new NanoidClient())->isValid($v), 'Must be valid Nano ID');

// Real-world form data normalization (with dedicated methods):
$formValidator = Validator::isAssociative([
    'name' => Validator::isString()
        ->nullifyEmpty()                                // Already implemented! ✅
        ->transform('trim')
        ->required(),
    'age' => Validator::isInt()
        ->nullifyEmpty()                                // Works across all types ✅
        ->coerce(),
    'salary' => Validator::isFloat()
        ->coerce(),                                     // Enhanced: empty strings → 0.0
    'tags' => Validator::isArray()
        ->filterEmpty()                                 // Remove empty values + auto-reindex
        ->default([])
]);

// Compare: Before vs After
// Before (verbose):
->transform(fn($v) => $v === '' ? 0 : $v)               // Empty string to zero
->transform(fn($v) => array_filter($v))                 // Remove empty values
->transform(fn($v) => array_values($v))                 // Re-index array

// After (clean and discoverable):
->coerce()                                              // ✅ Enhanced: empty strings → 0!
->filterEmpty()                                         // ✅ Clean array method + auto-reindex!

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
- [ ] **`minItems()`** / **`maxItems()`** - Array length constraints
- [ ] **`contains()`** - Validates array contains specific item

### Array Transformations
- [x] ✅ **`transform()`** / **`pipe()`** - Generic transformation methods (already implemented!)
- [x] ✅ **`nullifyEmpty()`** - Convert empty arrays to null (already implemented!)
- [x] ✅ **`filterEmpty()`** - Remove empty/null values and reindex automatically (already implemented!)
- [ ] **Enhanced `filterEmpty(bool $filter = true)`** - Add boolean parameter for conditional filtering

### Removed from Scope
- ~~`unique()`~~ - Use `array_unique()` or Laravel Collections (complex deduplication logic)
- ~~`flatten()`~~ - Complex recursive logic, use Laravel Collections or `array_merge_recursive()`

### Numeric Enhancements
- [ ] **`nonNegative()`** - Validates numbers >= 0 (includes zero)
- [ ] **`nonPositive()`** - Validates numbers <= 0 (includes zero)
- [ ] **`gt()`** / **`gte()`** - Greater than / greater than or equal explicit comparisons
- [ ] **`lt()`** / **`lte()`** - Less than / less than or equal explicit comparisons

### Numeric Transformations
- [ ] **`clamp(min, max)`** - Restrict numbers to range (not obvious: max(min, min(max, value)))
- [x] ✅ **Enhanced `coerce()`** - Empty strings → 0 for numeric types (already implemented!)
- [x] ✅ **`nullifyEmpty()`** - Convert empty strings to null (already implemented!)
- [x] ✅ **`transform()`** / **`pipe()`** - Generic transformation methods (already implemented!)

### Removed from Scope
- ~~`round(precision)`~~ - Use `->pipe(fn($v) => round($v, $precision))` for clarity

### Universal Validators
- [ ] **Enhanced `required(bool $required = true)`** - Add boolean parameter to override required state (validator reusability)
- [ ] **Enhanced `coerce(bool $coerce = true)`** - Add boolean parameter to override coercion state (library flexibility)
- [ ] **Enhanced `nullifyEmpty(bool $nullify = true)`** - Add boolean parameter to override nullification (form vs API patterns)
- [ ] **`enum()`** - Validates value is one of predefined options (available on all validators)
- [ ] **`const()`** - Validates value equals specific constant

### Universal Transformations
- [x] ✅ **`transform()`** / **`pipe()`** - Generic transformation methods (already implemented on all validators!)

### Removed from Scope
- ~~`when(condition, transform)`~~ - Complex conditional logic, use external control flow

### Enhanced Error Handling
- [ ] **Structured error codes** - Programmatic error identification
- [ ] **Error path enhancement** - Full field paths for nested validation errors

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

## 🏗️ Future Release (v0.6.0) - Advanced Schema Features

### Schema Composition
- [ ] **`additionalProperties()`** - Control undefined properties in schemas
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

## 🔮 Long-term Vision (v1.0.0+)

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

## 🎯 Ongoing Quality Initiatives

### Documentation
- [x] ✅ Comprehensive guide restructure
- [x] ✅ API reference documentation
- [x] ✅ Real-world examples
- [ ] **Video tutorials** - Getting started screencasts
- [ ] **Interactive documentation** - Runnable examples

### Testing & Quality
- [x] ✅ Organized test suite (9 focused test files, 103 tests, 250 assertions)
- [x] ✅ 100% PHPStan compliance
- [x] ✅ PHP-CS-Fixer standards
- [x] ✅ Static logical combinators test coverage
- [ ] **Mutation testing** - Enhanced test quality verification
- [ ] **Property-based testing** - Randomized validation testing
- [ ] **Performance benchmarking** - Continuous performance monitoring

### Community & Ecosystem
- [ ] **Plugin architecture** - Third-party validator extensions
- [ ] **Community validators** - Shared validation library
- [ ] **Migration tools** - Automated upgrades between versions

## 🔄 Development Process

### Feature Development Workflow
1. **Research & Design** - Community input, API design
2. **Implementation** - Core functionality with tests
3. **Documentation** - Comprehensive guides and examples
4. **Review & Testing** - Code review, edge case testing
5. **Release & Feedback** - Version release, community feedback

### Quality Gates
- ✅ **100% test coverage** for new features
- ✅ **PHPStan level max** compliance
- ✅ **Backward compatibility** preservation
- ✅ **Performance benchmarking** - No regressions
- ✅ **Documentation completeness** - Guides and examples
- ✅ **Ecosystem integration** - Prefer extensibility over reimplementation

### Release Cadence
- **Major releases** (x.0.0) - Breaking changes, major features (quarterly)
- **Minor releases** (x.y.0) - New features, enhancements (monthly)
- **Patch releases** (x.y.z) - Bug fixes, documentation (as needed)

## 📊 Success Metrics

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
