# Lemmon Validator - Roadmap

This roadmap outlines the planned features for future releases, prioritizing commonly needed functionalities for application development.

**Released:**
- `0.1.0` - Initial release.

## Release 0.2: Common Formats & Utility Constraints

This release focuses on introducing highly practical format validators for strings and other frequently used utility constraints.

*   **StringValidator**:
    *   Implement `email()`: Validates a string as an email address.
    *   Implement `url()`: Validates a string as a URL.
    *   Implement `uuid()`: Validates a string as a UUID.
    *   Implement `ip()`: Validates a string as an IPv4 or IPv6 address.
    *   Implement `datetime()`: Validates a string as a date-time format (e.g., ISO 8601).
    *   Implement `date()`: Validates a string as a date format.
    *   Implement `time()`: Validates a string as a time format.
*   **NumberValidator**:
    *   Implement `isFloat()` / `isNumber()`: For floating-point numbers.
    *   Implement `multipleOf(float $value)`: Validates that a number is a multiple of a given number.
*   **ArrayValidator**:
    *   Implement `uniqueItems(bool $unique = true)`: Validates that all items in an array are unique.
*   **Enum & Const**:
    *   Implement `enum(array $allowedValues)`: Validates that a value is one of a predefined set of values (for all `FieldValidator` types).
    *   Implement `const(mixed $constantValue)`: Validates that a value is exactly equal to a specific constant.

## Release 0.3: Advanced Structure & Relationships

This release introduces more sophisticated validation rules for complex data structures and their interdependencies.

*   **SchemaValidator (Object Properties)**:
    *   Implement `additionalProperties(FieldValidator|bool $validatorOrBoolean)`: Controls whether properties not explicitly defined in the schema are allowed, and optionally validates them against a subschema.
    *   Implement `patternProperties(array $regexToValidatorMap)`: Validates properties whose names match a given regular expression against a subschema.
    *   Implement `propertyNames(StringValidator $validator)`: Validates the names of all properties in an object against a string schema.
    *   Implement `dependencies(array $dependencies)`: Defines conditional validation based on the presence or value of other properties.
*   **ArrayValidator**:
    *   Implement `contains(FieldValidator $validator)`: Validates that an array contains at least one item that matches a given subschema.
    *   Implement `additionalItems(FieldValidator|bool $validatorOrBoolean)`: Controls whether items beyond those defined in `items` (when `items` is an array of schemas) are allowed, and optionally validates them.

## Release 0.4: Logical Combinators

This release introduces the powerful logical combinators for expressing complex validation rules.

*   **Combinators (New `Combinator` class or methods on `FieldValidator`)**:
    *   Implement `allOf(array $validators)`: Value must be valid against all provided subschemas.
    *   Implement `anyOf(array $validators)`: Value must be valid against at least one of the provided subschemas.
    *   Implement `not(FieldValidator $validator)`: Value must *not* be valid against the provided subschema.

## Ongoing Tasks (Across All Releases)

*   **Documentation**:
    *   Maintain comprehensive PHPDoc comments for all new and existing methods/properties.
    *   Continuously update `README.md` with new usage examples.
    *   Develop a dedicated "Advanced Usage" guide or wiki for complex features.
*   **Testing**:
    *   Maintain high unit test coverage for all new features.
    *   Implement integration tests for complex schema interactions (e.g., nested schemas, combinators).
    *   Consider property-based testing for robust validation.
*   **Performance Optimization**:
    *   Regularly profile and optimize critical validation paths, especially for large schemas or inputs.
*   **Error Reporting**:
    *   Continuously refine error messages and error paths for complex nested validations to ensure they are clear and actionable.
*   **Type Safety**:
    *   Leverage PHP's type system and static analysis (PHPStan) to ensure maximum type safety throughout the library.