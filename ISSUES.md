# Project Issues and Suggestions

This document tracks identified bugs, architectural inconsistencies, and potential improvements for the Lemmon Validator library.

## 1. Architectural Suggestions

### IntValidator Coercion Precision

`IntValidator` uses `is_numeric()` during coercion, which means a string like `"1.5"` is successfully coerced (truncated) to `1`. While "form-safe," this truncation can hide data precision issues. Consider providing a way to enforce strict integer strings or documenting this behavior clearly.

### UnitEnum Support

The `enum()` validator currently only supports `BackedEnum`. Adding support for basic `UnitEnum` (cases only) would increase utility for projects that do not use backed values for all enums.

### Mixed Type Coercion in Combinators

The static combinators `Validator::anyOf()`, `allOf()`, and `not()` return a "mixed" validator that does not support top-level coercion. Users expecting coercion must enable it on individual sub-validators.

---

## 2. Feature Suggestions

### Built-in String Sanitizers

Common operations like `trim()`, `lowercase()`, and `uppercase()` are currently implemented via `pipe('trim')`. Adding them as first-class methods (e.g., `->trim()`) would improve discoverability and follow the fluent API pattern of other validators.

### Explicit nullable() Method

Although fields are optional (allow null) by default, adding an explicit `->nullable()` method (as an alias for default behavior) can improve code readability and intent, especially when contrasted with `->required()`.

### Instance Validation

Add a validator for checking object instances (e.g., `Validator::isInstance(MyClass::class)`).

### Error Message Templates

Implement a more flexible way to configure global error message templates for core constraints (like `minLength` or `between`) to support localization and consistent branding across applications.
