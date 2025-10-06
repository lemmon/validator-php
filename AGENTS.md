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

## 🚀 Advanced Features

### Validation Capabilities
- **Static Logical Combinators** - `Validator::allOf()`, `Validator::anyOf()`, `Validator::not()` for complex rule composition and mixed-type validation
- **Instance Logical Combinators** - `allOf()`, `anyOf()`, `not()` instance methods for chaining validation rules
- **Context-Aware Validation** - Custom validators receive `(value, key, input)` parameters
- **Comprehensive Error Collection** - All validation errors collected, not just the first failure
- **Smart Type Coercion** - Configurable automatic type conversion
- **Fluent API** - Chainable method calls for readable validation code

### Developer Experience
- **Dual Validation Methods** - `validate()` (exception-based) and `tryValidate()` (tuple-based)
- **Custom Validation** - `addValidation()` method for business logic integration
- **Schema Validation** - Nested structure validation with hierarchical error reporting

## 📚 Documentation Structure

### Getting Started
- **Installation & Setup** - Requirements, installation, verification
- **Basic Usage** - Fundamental patterns, validation methods, common use cases
- **Core Concepts** - Architecture understanding, validation flow, performance considerations

### Focused Guides
- **String Validation** - Complete format validation suite with practical examples
- **Numeric Validation** - Integer and float validation with shared constraints
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
- **Testing** - Pest PHP with organized test suite (9 focused test files, 76 tests, 208 assertions)
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
├── FieldValidatorTest.php           # Base functionality
├── NumericConstraintsTraitTest.php  # Shared numeric logic
└── ValidatorStaticCombinatorsTest.php # Static logical combinators
```

## 🎯 Project Status

### Current Version: 0.4.0
- ✅ Complete string validation suite
- ✅ Dedicated float validator with numeric constraints
- ✅ Static logical combinators for advanced validation logic
- ✅ Instance logical combinators for chaining validation rules
- ✅ Enhanced mixed-type validation support
- ✅ Comprehensive error collection and context-aware validation
- ✅ Organized test suite and enterprise documentation
- ✅ 100% PHPStan compliance and PHP-CS-Fixer standards

### Key Metrics
- **8 validator types** covering all PHP data types
- **28+ built-in validation methods** including static logical combinators
- **4,000+ lines of documentation** with practical examples
- **76 unit tests** with comprehensive coverage (208 assertions)
- **Zero technical debt** with modern PHP 8.1+ codebase

## 🔮 Vision & Roadmap

This library aims to be the definitive validation solution for PHP applications, providing:
- **Developer Productivity** - Intuitive API with excellent documentation
- **Type Safety** - Leveraging PHP's type system for robust validation
- **Performance** - Optimized validation logic for high-throughput applications
- **Extensibility** - Custom validation support for business-specific requirements
- **Enterprise Ready** - Comprehensive error handling and structured feedback

The project maintains a clear separation between completed features (CHANGELOG), strategic planning (ROADMAP), and innovative ideas (IDEAS), ensuring focused development and clear project management.
