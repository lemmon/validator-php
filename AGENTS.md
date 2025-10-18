# Lemmon Validator - Project Overview

A comprehensive, fluent validation library for PHP inspired by Valibot and Zod. This project provides type-safe validation with a symmetrical API for handling different data structures, advanced validation capabilities, and enterprise-grade documentation.

## 🏗️ Architecture

### Core Factory Pattern
- **`Validator`** - Static factory creating type-specific validators (`isString()`, `isInt()`, `isFloat()`, `isArray()`, `isAssociative()`, `isObject()`, `isBool()`)
- **`FieldValidator`** - Abstract base class with unified validation interface and shared functionality
- **`ValidationException`** - Structured exception handling with comprehensive error collection

### Type-Specific Validators
- **`StringValidator`** - Format validation (email, URL, UUID, IP), length constraints, pattern matching
- **`IntValidator`** / **`FloatValidator`** - Numeric constraints via shared `NumericConstraintsTrait`
- **`ArrayValidator`** - Indexed array validation with optional item validation
- **`AssociativeValidator`** / **`ObjectValidator`** - Schema-based validation for complex structures
- **`BoolValidator`** - Boolean validation with intelligent coercion

### Shared Components
- **`NumericConstraintsTrait`** - Common numeric validations (`min()`, `max()`, `multipleOf()`, `positive()`, `negative()`)
- **`PipelineType`** - Type-safe enum for pipeline operations (`VALIDATION`, `TRANSFORMATION`) with IDE support and refactoring safety

## 🚀 Advanced Features

### Validation Capabilities
- **Static Logical Combinators** - `Validator::allOf()`, `Validator::anyOf()`, `Validator::not()` for complex rule composition and mixed-type validation
- **New `satisfies*` API** - Enhanced instance logical combinators (`satisfiesAny()`, `satisfiesAll()`, `satisfiesNone()`) with support for mixed validators/callables
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

### Developer Experience
- **Dual Validation Methods** - `validate()` (exception-based) and `tryValidate()` (tuple-based)
- **Schema Validation** - Nested structure validation with hierarchical error reporting
- **Comprehensive Documentation** - Complete guides, API reference, and real-world examples

## 📚 Documentation Structure

### Getting Started
- **Installation & Setup** - Requirements, installation, verification
- **Basic Usage** - Fundamental patterns, validation methods, common use cases
- **Core Concepts** - Architecture understanding, validation flow, performance considerations

### Focused Guides
- **String Validation** - Complete format validation suite with practical examples
- **Numeric Validation** - Integer and float validation with shared constraints
- **Array Validation** - Indexed array validation with filtering and item validation
- **Object Validation** - Schema-based validation for complex structures
- **Custom Validation** - Business logic integration and context-aware validation
- **Error Handling** - Exception vs tuple patterns, structured error reporting

### API Reference
- **Validator Factory** - Complete factory method documentation with usage patterns
- **Type-Specific APIs** - Detailed method references for each validator type

### Practical Examples
- **Form Validation** - Contact forms, user registration, e-commerce products
- **Multi-Step Forms** - Complex validation workflows with session handling
- **Real-World Schemas** - Business logic validation patterns

## 🔧 Development Workflow

### Code Quality
- **Testing** - Pest PHP with organized test suite (10 focused test files, 135 tests, 390 assertions)
- **Static Analysis** - PHPStan at maximum level for type safety
- **Code Style** - PHP-CS-Fixer for consistent formatting
- **Performance** - Optimized validation logic with eliminated code duplication

### Development Tools
- **Debugging** - `symfony/var-dumper` integration
- **Error Handling** - `symfony/error-handler` for development
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

## 🎯 Project Status

### Current Version: 0.6.0
- ✅ Complete string validation suite with form-safe handling
- ✅ Dedicated float validator with numeric constraints
- ✅ Static logical combinators for advanced validation logic
- ✅ Instance logical combinators for chaining validation rules
- ✅ Enhanced mixed-type validation support
- ✅ Type-aware transformation system with `transform()` and `pipe()` methods
- ✅ Form-safe coercion - empty strings convert to `null` (not dangerous `0`/`false`)
- ✅ Intuitive custom validation with `satisfies()` method
- ✅ Comprehensive error collection and context-aware validation
- ✅ Complete documentation with `nullifyEmpty()` coverage across all guides
- ✅ Organized test suite and enterprise documentation
- ✅ 100% PHPStan compliance and PHP-CS-Fixer standards

### Key Metrics
- **8 validator types** covering all PHP data types
- **35+ built-in validation methods** including static logical combinators, array filtering, type-aware transformations, form-safe coercion, and intuitive custom validation (all using modern `satisfies()` API internally)
- **5,000+ lines of documentation** with practical examples and comprehensive coverage
- **135 unit tests** with comprehensive coverage (390 assertions)
- **Zero technical debt** with modern PHP 8.1+ codebase
- **Zero dead links** in documentation with seamless navigation
- **Complete API documentation** with accurate method signatures and examples

## 🔮 Vision & Roadmap

This library aims to be the definitive validation solution for PHP applications, providing:
- **Developer Productivity** - Intuitive API with excellent documentation
- **Type Safety** - Leveraging PHP's type system for robust validation
- **Performance** - Optimized validation logic for high-throughput applications
- **Extensibility** - Custom validation support for business-specific requirements
- **Enterprise Ready** - Comprehensive error handling and structured feedback

The project maintains a clear separation between completed features (CHANGELOG), strategic planning (ROADMAP), and innovative ideas (IDEAS), ensuring focused development and clear project management.
