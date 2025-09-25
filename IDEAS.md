# Lemmon Validator - Future Ideas and Suggestions

This document captures ideas and suggestions for potential future enhancements to the Lemmon Validator library, beyond the current roadmap. These are not prioritized but serve as a backlog of possibilities for further development.

## Recently Implemented (v0.3.0) ✅

The following ideas were successfully implemented in the recent major release:

✅ **Logical Combinators** - `allOf()`, `anyOf()`, and `not()` methods for complex validation logic composition.

✅ **Enhanced Error Collection** - Comprehensive error reporting that collects all validation errors instead of stopping at the first failure.

✅ **Context-Aware Validation** - Custom validators now receive `(value, key, input)` parameters for sophisticated validation logic.

✅ **Shared Validation Logic** - `NumericConstraintsTrait` eliminates code duplication between numeric validators.

✅ **Comprehensive String Validation** - Complete suite of format validators including email, URL, UUID, IP, datetime, pattern matching, and length constraints.

## New Ideas from Recent Development

## 5. Validation Performance Profiling

*   **Concept**: Introduce optional performance monitoring and profiling capabilities for complex validation chains.
*   **Benefit**: Identify bottlenecks in complex validation schemas, especially useful for high-throughput applications.
*   **Implementation**: Optional profiler that tracks validation time per validator and provides optimization suggestions.

## 6. Validation Schema Serialization

*   **Concept**: Ability to serialize/deserialize validation schemas to/from JSON or other formats.
*   **Benefit**: Share validation schemas between frontend/backend, store validation rules in databases, or generate documentation.
*   **Implementation**: `$schema->toArray()` and `Validator::fromArray($config)` methods.

## 7. Test Suite Organization Patterns

*   **Concept**: The recent test suite split into focused files demonstrates excellent patterns that could be documented as best practices.
*   **Benefit**: Other PHP projects could benefit from similar test organization strategies.
*   **Implementation**: Create documentation or blog post about test organization patterns for validation libraries.

## Existing Ideas (Still Relevant)

## 1. Data Transformation / Piping

*   **Concept**: Introduce a more general `transform(callable $callback)` method (or a `pipe()` concept) that allows users to apply arbitrary transformations to the validated data *after* validation but *before* it's returned. This is distinct from `coerce()` which is typically about type conversion.
*   **Benefit**: Very powerful for data normalization, sanitization, or shaping. For example, you could validate a string, then transform it into a `DateTime` object, or encrypt it, all within the validation chain.
*   **Example**: `Validator::isString()->datetime()->transform(fn($value) => new DateTime($value))`

## 2. Enhanced Error Pathing for Nested Schemas

*   **Concept**: For errors in nested schemas, enhance the error reporting to include the full "path" to the invalid field.
*   **Benefit**: When dealing with deeply nested data structures, knowing the exact path (e.g., `user.address.street` instead of just `street`) to an error is invaluable for debugging and user feedback.
*   **Example**: Instead of `['street' => ['Value is required.']]`, the error object could contain a `path` property (e.g., `['path' => 'user.address.street', 'message' => 'Value is required.']`) or the error key could be the full path.
*   **Note**: This becomes more valuable as the library gains adoption and users work with deeper nested structures.

## 3. Schema Manipulation Methods

*   **Concept**: Introduce methods on `AssociativeValidator`/`ObjectValidator` that allow for easy manipulation and reuse of existing schemas.
*   **`partial()`**: Makes all fields in a schema optional. Useful for PATCH requests where only a subset of fields might be sent.
*   **`pick(array $keys)`**: Creates a new schema containing only the specified keys from an existing schema.
*   **`omit(array $keys)`**: Creates a new schema excluding the specified keys from an existing schema.
*   **`merge(AssociativeValidator $otherSchema)`**: Combines two schemas into one.
*   **Benefit**: Promotes schema reusability and reduces boilerplate when you need slightly different versions of a base schema.

## 4. Error Codes

*   **Concept**: Assign unique, programmatic error codes to common validation failures (e.g., `STRING_TOO_SHORT`, `INVALID_EMAIL_FORMAT`).
*   **Benefit**: Allows for easier programmatic handling of specific error types in the application layer, beyond just parsing the human-readable message.
*   **Implementation**: Could extend `ValidationException` to include error codes alongside messages.
