# Lemmon Validator - Project Overview

A comprehensive, fluent validation library for PHP inspired by Valibot and Zod. This project provides type-safe validation with a symmetrical API for handling different data structures, advanced validation capabilities, and enterprise-grade documentation.

## Architecture

- **Root Namespace** - Runtime classes live under `Lemmon\Validator`; tests use `Lemmon\Tests`

### Core Factory Pattern
- **`Validator`** - Static factory creating type-specific validators (`isString()`, `isInt()`, `isFloat()`, `isArray()`, `isAssociative()`, `isObject()`, `isBool()`)
- **`FieldValidator`** - Abstract base class with unified validation interface and shared functionality
- **`ValidationException`** - Structured exception handling with comprehensive error collection

### Type-Specific Validators
- **`StringValidator`** - Format validation (email, URL, UUID with version variants, IP with version variants, hostname, domain, time, base64 with variants, hex), length constraints, pattern matching
- **`IntValidator`** / **`FloatValidator`** - Numeric constraints via shared `NumericConstraintsTrait` (includes port validation for IntValidator)
- **`ArrayValidator`** - Indexed array validation with optional item validation
- **`AssociativeValidator`** / **`ObjectValidator`** - Schema-based validation for complex structures
- **`BoolValidator`** - Boolean validation with intelligent coercion

### Shared Components
- **`NumericConstraintsTrait`** - Common numeric validations (`min()`, `max()`, `multipleOf()`, `positive()`, `negative()`, comparison helpers)
- **`PipelineType`** - Type-safe enum for pipeline operations (`VALIDATION`, `TRANSFORMATION`) with IDE support and refactoring safety
- **Variant Enums** - `IpVersion`, `Base64Variant`, and `UuidVariant` enums for type-safe variant selection in format validators

## Advanced Features

### Validation Capabilities
- **Static Logical Combinators** - `Validator::allOf()`, `Validator::anyOf()`, `Validator::not()` for complex rule composition and mixed-type validation
- **New `satisfies*` API** - Enhanced instance logical combinators (`satisfiesAny()`, `satisfiesAll()`, `satisfiesNone()`) with support for mixed validators/callables
- **Enum-Based Variant Flags** - Type-safe variant selection for IP addresses (`IpVersion`), Base64 encoding (`Base64Variant`), and UUID versions (`UuidVariant`) with consistent API pattern
- **Smart Null Handling** - Revolutionary null handling system where validations skip `null` unless `required()`, transformations always execute, and order is independent
- **Form-Safe Coercion** - Empty strings convert to `null` (not dangerous `0`/`0.0`/`false`) for primitives, empty structures for objects/arrays
- **Array Filtering** - `filterEmpty()` method removes empty values while maintaining indexed array structure
- **Type-Aware Transformations** - Revolutionary `transform()` and `pipe()` system with intelligent type context switching
- **Unified Pipeline Architecture** - Single conceptual pipeline with hybrid execution (error collection for validations, fail-fast for transformations)
- **Custom Validation** - Enhanced `satisfies()` method accepting `FieldValidator` instances or callables with optional error messages (all internal validators migrated from deprecated `addValidation()`)
- **Context-Aware Validation** - Custom validators receive `(value, key, input)` parameters
- **Comprehensive Error Collection** - All validation errors collected, not just the first failure
- **Smart Type Coercion** - Configurable automatic type conversion with form-friendly defaults
- **Fluent API with guaranteed execution order** - Chainable method calls that execute in the exact order written
- **Extensibility Philosophy** - Focus on core validation principles; external libraries encouraged for advanced/specialized validators via `satisfies()`

### Developer Experience
- **Dual Validation Methods** - `validate()` (exception-based) and `tryValidate()` (tuple-based)
- **Schema Validation** - Nested structure validation with hierarchical error reporting
- **Comprehensive Documentation** - Complete guides, API reference, and real-world examples

## Documentation Structure

### Getting Started
- **Installation & Setup** - Requirements, installation, verification
- **Basic Usage** - Fundamental patterns, validation methods, common use cases
- **Core Concepts** - Architecture understanding, validation flow, performance considerations

### Focused Guides
- **String Validation** - Complete format validation suite (email, URL, UUID, IP, hostname, domain, time, base64, hex) with enum-based variants and practical examples
- **Numeric Validation** - Integer and float validation with shared constraints, including port validation
- **Array Validation** - Indexed array validation with filtering and item validation
- **Object Validation** - Schema-based validation for complex structures
- **Custom Validation** - Business logic integration, context-aware validation, and external library integration patterns
- **Error Handling** - Exception vs tuple patterns, structured error reporting

### API Reference
- **Validator Factory** - Complete factory method documentation with usage patterns
- **Type-Specific APIs** - Detailed method references for each validator type

### Practical Examples
- **Form Validation** - Contact forms, user registration, e-commerce products
- **Multi-Step Forms** - Complex validation workflows with session handling
- **Real-World Schemas** - Business logic validation patterns

## Development Workflow

### Code Quality
- **Testing** - Pest PHP with organized, focused test suite
- **Static Analysis** - PHPStan at maximum level for type safety
- **Code Style** - Mago for linting/formatting; scripts map `composer lint`/`composer fix` to Mago
- **Performance** - Optimized validation logic with eliminated code duplication

### Development Guidelines
- **Documentation** - Add concise PHPDoc blocks where behavior is not immediately obvious, especially for helpers touching I/O streams
- **ASCII Punctuation** - Stick to ASCII punctuation in code and docs (prefer -- over em dashes) so diffs stay predictable
- **Emoji Usage** - Reserve emojis for rare emphasis; moderate use is fine for emphasis, but skip emoji-driven lists
- **Task Tracking** - Use GitHub-style checkboxes (`- [ ]` for pending, `- [x]` for completed) in ROADMAP.md for clear progress tracking
- **Task Pool** - Maintain immediate priorities in `TASKS.md`; keep it short, prune completed items, and avoid numbering to minimize churn
- **Commit Standards** - Follow Conventional Commits spec (e.g., `fix:`, `refactor:`, `docs:`)
- **Commit Scope** - Each commit should address a single concern; tests and implementation can ship together, but unrelated formatting belongs elsewhere
- **Commit Format** - Use concise Conventional Commit summaries: `<type>(<scope>): <short action>`. Avoid verbose release blurbs in commit messages; keep release notes in CHANGELOG/release tagging.
- **Git Tags** - Prefer annotated tags for releases (author, date, message/signing) over lightweight tags
- **Tag Format** - Annotated tags should use `vX.Y.Z - <concise headline>`; keep detailed notes in CHANGELOG/releases

### Development Tools
- **Debugging** - `symfony/var-dumper` integration
- **Error Handling** - `symfony/error-handler` for development
- **Composer Normalization** - `ergebnis/composer-normalize` for consistent composer.json formatting
- **Composer Scripts** - `test`, `lint`, `fix`, `analyse` commands

### Test Organization
```
tests/
├── AssociativeValidatorTest.php     # Schema validation
├── ObjectValidatorTest.php          # stdClass validation
├── ArrayValidatorTest.php           # Indexed arrays
├── StringValidatorTest.php          # String formats
├── IntValidatorTest.php             # Integer constraints
├── FloatValidatorTest.php           # Float constraints
├── BoolValidatorTest.php            # Boolean validation
├── FieldValidatorTest.php           # Base functionality
├── NumericConstraintsTraitTest.php  # Shared numeric logic
└── ValidatorStaticCombinatorsTest.php # Static logical combinators
```

## Architecture Overview

### Core Components
- **8 validator types** covering all PHP data types
- **Unified pipeline architecture** with smart null handling and execution order guarantees
- **Type-safe internal structure** using modern PHP 8.1+ enums
- **Comprehensive test coverage** across all validator types
- **Enterprise-grade documentation** with practical examples and complete API reference

## Vision & Roadmap

This library aims to be the definitive validation solution for PHP applications, providing:
- **Developer Productivity** -- Intuitive API with excellent documentation
- **Type Safety** -- Leveraging PHP's type system for robust validation
- **Performance** -- Optimized validation logic for high-throughput applications
- **Extensibility** -- Custom validation support for business-specific requirements
- **Enterprise Ready** -- Comprehensive error handling and structured feedback

The project maintains a clear separation between completed features (CHANGELOG), strategic planning (ROADMAP), and innovative ideas (IDEAS), ensuring focused development and clear project management.
