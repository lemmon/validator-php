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
use Lemmon\Validator;

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

For associative arrays, use `Validator::isAssociative()` instead.

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

## Common Patterns

### Array Length Constraints

```php
// Using oneOf for specific arrays
$validator = Validator::isArray()->oneOf([[1, 2], [3, 4]]);
$result = $validator->validate([1, 2]); // Valid
$validator->validate([1, 2, 3]); // Throws ValidationException
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
use Lemmon\ValidationException;

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
- Explore [Custom Validation](custom-validation.md) for business logic
- Check out [Error Handling](error-handling.md) for advanced error management
- See [Advanced Patterns](advanced-patterns.md) for complex validation scenarios
