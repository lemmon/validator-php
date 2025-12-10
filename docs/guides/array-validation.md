# Array Validation

This guide covers array validation using the `ArrayValidator` class, which handles indexed arrays (lists) with optional item validation.

## Table of Contents

- [Basic Array Validation](#basic-array-validation)
- [Item Validation](#item-validation)
- [Type Coercion](#type-coercion)
- [Common Patterns](#common-patterns)
- [Error Handling](#error-handling)

## Basic Array Validation

### Simple Array Validation

```php
use Lemmon\Validator\Validator;

// Validate indexed arrays
$validator = Validator::isArray();

$result = $validator->validate([1, 2, 3, 'foo']);
// Result: [1, 2, 3, 'foo']

$result = $validator->validate(['a', 'b', 'c']);
// Result: ['a', 'b', 'c']

$result = $validator->validate([]);
// Result: []
```

### Required vs Optional Arrays

```php
// Optional array (allows null)
$optional = Validator::isArray();
$result = $optional->validate(null); // null

// Required array
$required = Validator::isArray()->required();
$required->validate(null); // Throws ValidationException

// Array with default value
$withDefault = Validator::isArray()->default(['default']);
$result = $withDefault->validate(null); // ['default']
```

### Associative Arrays are Rejected

The `ArrayValidator` only accepts indexed arrays (lists). Associative arrays will be rejected:

```php
$validator = Validator::isArray();

// This will throw ValidationException
$validator->validate(['key' => 'value']);
```

For associative arrays, use `Validator::isAssociative()` instead. See the [Object & Schema Validation](object-validation.md#associative-array-validation) guide for complete documentation on associative array validation.

## Item Validation

### Validating Array Items

Use the `items()` method to validate each item in the array:

```php
// Array of strings
$stringArray = Validator::isArray()->items(Validator::isString());
$result = $stringArray->validate(['foo', 'bar', 'baz']);
// Result: ['foo', 'bar', 'baz']

// Array of integers
$intArray = Validator::isArray()->items(Validator::isInt());
$result = $intArray->validate([1, 2, 3, 4]);
// Result: [1, 2, 3, 4]

// Array of emails
$emailArray = Validator::isArray()->items(
    Validator::isString()->email()
);
$result = $emailArray->validate([
    'user@example.com',
    'admin@test.org'
]);
```

### Complex Item Validation

```php
// Array of user objects
$userArray = Validator::isArray()->items(
    Validator::isAssociative([
        'name' => Validator::isString()->required(),
        'age' => Validator::isInt()->min(0)->max(150),
        'email' => Validator::isString()->email()
    ])
);

$users = [
    ['name' => 'John', 'age' => 30, 'email' => 'john@example.com'],
    ['name' => 'Jane', 'age' => 25, 'email' => 'jane@example.com']
];

$result = $userArray->validate($users);
```

### Item Validation with Coercion

```php
// Array of integers with coercion
$intArray = Validator::isArray()->items(
    Validator::isInt()->coerce()
);

$result = $intArray->validate(['1', '2', '3']);
// Result: [1, 2, 3] (strings converted to integers)
```

## Array Filtering

### Removing Empty Values with `filterEmpty()`

The `filterEmpty()` method removes empty values (empty strings and `null`) from arrays and automatically reindexes them to maintain the indexed array structure:

```php
$validator = Validator::isArray()->filterEmpty();

// Remove empty values and reindex
$result = $validator->validate(['apple', '', 'banana', null, 'cherry']);
// Result: ['apple', 'banana', 'cherry'] (properly reindexed: [0, 1, 2])

// Works with mixed data types
$mixedValidator = Validator::isArray()->filterEmpty();
$result = $mixedValidator->validate([1, '', 2, null, 3, 0, false]);
// Result: [1, 2, 3, 0, false] (only empty strings and null removed)
```

### Combining with Item Validation

```php
// Filter empty values then validate remaining items
$emailValidator = Validator::isArray()
    ->filterEmpty()                    // Remove empty values first
    ->items(Validator::isString()->email()); // Then validate emails

$emails = ['john@example.com', '', 'jane@example.com', null, 'invalid-email'];
[$valid, $result, $errors] = $emailValidator->tryValidate($emails);
// $result would be ['john@example.com', 'jane@example.com'] (filtered)
// $errors would contain validation error for 'invalid-email'
```

### Real-World Use Cases

```php
// Form data with optional fields
$tagsValidator = Validator::isArray()
    ->filterEmpty()                           // Remove empty tag inputs
    ->items(Validator::isString()->minLength(2)); // Validate remaining tags

$formTags = ['php', '', 'javascript', null, 'css', ''];
$result = $tagsValidator->validate($formTags);
// Result: ['php', 'javascript', 'css']

// CSV-like data processing
$csvRowValidator = Validator::isArray()
    ->filterEmpty()                     // Remove empty CSV cells
    ->items(Validator::isString()->trim()); // Clean remaining values

$csvRow = ['John', '', 'Doe', null, '30', ''];
$result = $csvRowValidator->validate($csvRow);
// Result: ['John', 'Doe', '30']
```

## Type Coercion

The `ArrayValidator` supports several coercion strategies when `coerce()` is enabled:

### Scalar to Array Coercion

```php
$validator = Validator::isArray()->coerce();

// String to single-item array
$result = $validator->validate('single');
// Result: ['single']

// Number to single-item array
$result = $validator->validate(123);
// Result: [123]

// Boolean to single-item array
$result = $validator->validate(true);
// Result: [true]

// Empty string to empty array
$result = $validator->validate('');
// Result: []
```

### Associative Array to Indexed Array

```php
$validator = Validator::isArray()->coerce();

// Associative array gets converted to indexed array (values only)
$result = $validator->validate(['key1' => 'value1', 'key2' => 'value2']);
// Result: ['value1', 'value2']

$result = $validator->validate(['a' => 1, 'b' => 2, 'c' => 3]);
// Result: [1, 2, 3]
```

### Coercion with Null Handling

```php
// Without required - null passes through
$validator = Validator::isArray()->coerce();
$result = $validator->validate(null); // null

// With default - null uses default
$validator = Validator::isArray()->coerce()->default(['default']);
$result = $validator->validate(null); // ['default']

// With required - null throws error
$validator = Validator::isArray()->coerce()->required();
$validator->validate(null); // Throws ValidationException
```

## Array Length Constraints

### Minimum and Maximum Items

Use `minItems()` and `maxItems()` to validate array length, similar to `minLength()` and `maxLength()` for strings:

```php
// Minimum items constraint
$validator = Validator::isArray()->minItems(3);
$result = $validator->validate([1, 2, 3]); // Valid
$result = $validator->validate([1, 2, 3, 4]); // Valid
$validator->validate([1, 2]); // Throws ValidationException: "Value must contain at least 3 items"

// Maximum items constraint
$validator = Validator::isArray()->maxItems(5);
$result = $validator->validate([1, 2, 3]); // Valid
$result = $validator->validate([1, 2, 3, 4, 5]); // Valid
$validator->validate([1, 2, 3, 4, 5, 6]); // Throws ValidationException: "Value must contain at most 5 items"

// Combined constraints
$validator = Validator::isArray()->minItems(2)->maxItems(4);
$result = $validator->validate([1, 2]); // Valid
$result = $validator->validate([1, 2, 3, 4]); // Valid
$validator->validate([1]); // Throws ValidationException: "Value must contain at least 2 items"
$validator->validate([1, 2, 3, 4, 5]); // Throws ValidationException: "Value must contain at most 4 items"
```

### Custom Error Messages

```php
$validator = Validator::isArray()
    ->minItems(3, 'Array must have at least 3 elements')
    ->maxItems(10, 'Array cannot exceed 10 elements');
```

### Array Contains Validation

The `contains()` method validates that an array contains a specific value or an item matching a validator:

```php
// Contains specific value (strict comparison)
$validator = Validator::isArray()->contains('banana');
$result = $validator->validate(['apple', 'banana', 'cherry']); // Valid
$validator->validate(['apple', 'cherry']); // Throws ValidationException

// Contains value with strict type checking
$validator = Validator::isArray()->contains(0);
$result = $validator->validate([0, 1, 2]); // Valid (finds integer 0)
$validator->validate(['0', 1, 2]); // Throws ValidationException (string '0' !== integer 0)

// Contains item matching validator
$validator = Validator::isArray()->contains(Validator::isString()->email());
$result = $validator->validate(['not-email', 'test@example.com', 'also-not-email']); // Valid
$validator->validate(['not-email', 'also-not-email']); // Throws ValidationException

// Contains item matching complex validator
$validator = Validator::isArray()->contains(Validator::isInt()->positive());
$result = $validator->validate([-1, 0, 5, -2]); // Valid (contains positive integer 5)
$validator->validate([-1, 0, -2]); // Throws ValidationException (no positive integers)
```

### Combining Contains with Item Validation

```php
// Validate all items are strings AND array contains an email
$validator = Validator::isArray()
    ->items(Validator::isString())
    ->contains(Validator::isString()->email());

$result = $validator->validate(['hello', 'test@example.com', 'world']); // Valid
$validator->validate(['hello', 'world']); // Throws ValidationException (no email found)
$validator->validate(['hello', 123, 'test@example.com']); // Throws ValidationException (item validation fails first)
```

## Common Patterns

### Custom Array Validation

```php
// For specific array validation, use custom validation
$validator = Validator::isArray()->satisfies(
    fn($value) => in_array($value, [[1, 2], [3, 4]], true),
    'Array must be exactly [1, 2] or [3, 4]'
);
$result = $validator->validate([1, 2]); // Valid
$validator->validate([1, 2, 3]); // Throws ValidationException

// Note: oneOf() is not available on ArrayValidator as it doesn't make semantic sense for complex types
```

### Empty Array Handling

```php
// Nullify empty arrays
$validator = Validator::isArray()->nullifyEmpty();
$result = $validator->validate([]); // null
$result = $validator->validate([1, 2]); // [1, 2]

// Default for empty arrays
$validator = Validator::isArray()->default(['fallback']);
$result = $validator->validate(null); // ['fallback']
```

### Mixed Type Arrays

```php
// Array that can contain strings or numbers
$mixedArray = Validator::isArray()->items(
    Validator::anyOf([
        Validator::isString(),
        Validator::isInt(),
        Validator::isFloat()
    ])
);

$result = $mixedArray->validate(['hello', 42, 3.14]);
// Result: ['hello', 42, 3.14]
```

### Nested Array Validation

```php
// Array of arrays
$nestedArray = Validator::isArray()->items(
    Validator::isArray()->items(Validator::isString())
);

$result = $nestedArray->validate([
    ['a', 'b'],
    ['c', 'd'],
    ['e', 'f']
]);
```

## Error Handling

### Basic Error Handling

```php
use Lemmon\Validator\ValidationException;

$validator = Validator::isArray();

try {
    $validator->validate('not an array');
} catch (ValidationException $e) {
    echo $e->getMessage(); // "Validation failed"
    print_r($e->getErrors()); // ['Value must be an array']
}
```

### Item Validation Errors

```php
$validator = Validator::isArray()->items(Validator::isString());

try {
    $validator->validate(['valid', 123, 'also valid']);
} catch (ValidationException $e) {
    // Error will indicate which item failed validation
    print_r($e->getErrors());
}
```

### Using tryValidate for Error Handling

```php
$validator = Validator::isArray()->items(Validator::isInt());

[$valid, $result, $errors] = $validator->tryValidate(['1', 'invalid', '3']);

if (!$valid) {
    echo "Validation failed:\n";
    print_r($errors);
} else {
    echo "Valid array:\n";
    print_r($result);
}
```

## Advanced Examples

### File Upload Validation

```php
// Validate array of uploaded files
$fileValidator = Validator::isArray()->items(
    Validator::isAssociative([
        'name' => Validator::isString()->required(),
        'size' => Validator::isInt()->min(1)->max(10485760), // Max 10MB
        'type' => Validator::isString()->oneOf([
            'image/jpeg', 'image/png', 'image/gif'
        ])
    ])
);
```

### Tag System Validation

```php
// Array of unique tags
$tagValidator = Validator::isArray()->items(
    Validator::isString()
        ->pattern('/^[a-zA-Z0-9-_]+$/', 'Tags can only contain letters, numbers, hyphens, and underscores')
        ->minLength(2)
        ->maxLength(50)
);

$result = $tagValidator->validate(['php', 'validation', 'array-handling']);
```

### Configuration Array Validation

```php
// Validate configuration arrays
$configValidator = Validator::isArray()->items(
    Validator::anyOf([
        Validator::isString(),
        Validator::isInt(),
        Validator::isBool(),
        Validator::isArray() // Allow nested arrays
    ])
);

$config = ['debug' => true, 'timeout' => 30, 'hosts' => ['localhost', '127.0.0.1']];
$result = $configValidator->validate($config);
```

## Next Steps

- Learn about [Object & Schema Validation](object-validation.md) for structured data
- Explore [Custom Validation](custom-validation.md) for business logic and complex validation scenarios
- Check out [Error Handling](error-handling.md) for advanced error management
- See [Form Validation Examples](../examples/form-validation.md) for practical examples
