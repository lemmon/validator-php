# Basic Usage

This guide covers the fundamental concepts and usage patterns of the Lemmon Validator.

## The Validator Factory

All validation starts with the `Validator` factory class, which provides static methods to create specific validator instances:

```php
use Lemmon\Validator;

// Create validators for different types
$stringValidator = Validator::isString();
$intValidator = Validator::isInt();
$floatValidator = Validator::isFloat();
$arrayValidator = Validator::isArray();
$objectValidator = Validator::isObject();
$associativeValidator = Validator::isAssociative();
$boolValidator = Validator::isBool();
```

## Basic Validation Pattern

Every validator follows the same pattern:

1. **Create** a validator
2. **Configure** it with rules (optional)
3. **Validate** the data

```php
// 1. Create and configure
$validator = Validator::isString()
    ->required()
    ->minLength(3)
    ->email();

// 2. Validate (throws exception on failure)
try {
    $result = $validator->validate('user@example.com');
    echo "Valid email: " . $result;
} catch (ValidationException $e) {
    echo "Validation failed: " . implode(', ', $e->getErrors());
}
```

## Two Validation Methods

### `validate()` - Exception-based

Throws a `ValidationException` if validation fails:

```php
$validator = Validator::isInt()->min(1)->max(100);

try {
    $result = $validator->validate(50); // Returns: 50
    echo "Valid: " . $result;
} catch (ValidationException $e) {
    echo "Invalid: " . implode(', ', $e->getErrors());
}
```

### `tryValidate()` - Tuple-based

Returns a tuple `[bool $valid, mixed $data, array $errors]`:

```php
$validator = Validator::isString()->email();

[$valid, $data, $errors] = $validator->tryValidate('invalid-email');

if ($valid) {
    echo "Valid email: " . $data;
} else {
    echo "Errors: " . implode(', ', $errors);
}
```

## Common Validator Methods

All validators inherit these methods from `FieldValidator`:

### Required vs Optional

```php
// Optional by default (accepts null)
$optional = Validator::isString();
[$valid, $data, $errors] = $optional->tryValidate(null); // $valid = true, $data = null

// Required (rejects null)
$required = Validator::isString()->required();
[$valid, $data, $errors] = $required->tryValidate(null); // $valid = false
```

### Default Values

```php
$withDefault = Validator::isString()->default('Hello World');

[$valid, $data, $errors] = $withDefault->tryValidate(null);
// $valid = true, $data = 'Hello World'
```

### Type Coercion

```php
// Enable automatic type conversion
$coercing = Validator::isInt()->coerce();

$result = $coercing->validate('123'); // Returns: 123 (integer)
$result = $coercing->validate('');    // Returns: null (form-safe!)
```

> **Form Safety Note**: Empty strings convert to `null` (not `0`/`0.0`/`false`) to prevent dangerous defaults in form handling. See [Core Concepts - Form-Safe Empty String Handling](core-concepts.md#form-safe-empty-string-handling) for details.

### Empty String Nullification

For explicit control over empty string handling, use `nullifyEmpty()`:

```php
// Convert empty strings to null
$nullifying = Validator::isString()->nullifyEmpty();

$result = $nullifying->validate('');      // Returns: null
$result = $nullifying->validate('hello'); // Returns: 'hello'

// Combined with defaults for form-safe optional fields
$optional = Validator::isString()
    ->nullifyEmpty()           // Empty strings → null
    ->default('Not provided'); // Use default for null

$result = $optional->validate('');        // Returns: 'Not provided'
$result = $optional->validate('John');    // Returns: 'John'

// Form-safe numeric validation
$safeQuantity = Validator::isInt()
    ->coerce()
    ->nullifyEmpty()     // Empty strings → null (not dangerous 0)
    ->min(1, 'Quantity must be at least 1');

$result = $safeQuantity->validate(''); // Validation fails (safe!)
$result = $safeQuantity->validate('5'); // Returns: 5
```

**When to use `nullifyEmpty()`:**
- **Form validation** where empty fields should be `null`
- **Optional fields** with meaningful defaults
- **Database schemas** preferring `NULL` over empty strings
- **API endpoints** normalizing empty strings to `null`

### Execution Order Matters

**Critical**: Methods execute in the **exact order written**. This is especially important when combining transformations with `required()`:

```php
// CORRECT: Trim → nullify → require (fails on empty input)
$requiredName = Validator::isString()
    ->pipe('trim')        // 1. Remove whitespace
    ->nullifyEmpty()      // 2. Empty strings → null
    ->required('Name is required'); // 3. Reject null values

$requiredName->validate('    '); // ❌ "Name is required" (correct!)

// ❌ DIFFERENT BEHAVIOR: Require → nullify (allows empty input)
$differentBehavior = Validator::isString()
    ->required('Name is required') // 1. Check if empty string is null (passes)
    ->nullifyEmpty();              // 2. Convert to null

$differentBehavior->validate(''); // Returns: null (different result!)
```

**Real-world form validation pattern:**

```php
// Common pattern: clean input → handle empty → validate requirements
$formValidator = Validator::isAssociative([
    'name' => Validator::isString()
        ->pipe('trim')                    // Clean whitespace
        ->nullifyEmpty()                  // Handle empty fields
        ->required('Name is required'),   // Enforce requirements

    'email' => Validator::isString()
        ->pipe('trim', 'strtolower')      // Clean and normalize
        ->nullifyEmpty()                  // Handle empty fields
        ->email('Invalid email format')   // Validate format
        ->required('Email is required'),  // Enforce requirements

    'age' => Validator::isInt()
        ->coerce()                        // Convert strings to int
        ->nullifyEmpty()                  // Handle empty fields (form-safe)
        ->min(18, 'Must be 18 or older'), // Optional field with constraints
]);
```

### Allowed Values

```php
$restricted = Validator::isString()->oneOf(['red', 'green', 'blue']);

$result = $restricted->validate('red'); // Valid
$result = $restricted->validate('yellow'); // ❌ ValidationException
```

## Schema Validation

For complex data structures, use schema validation:

### Associative Arrays

```php
$userSchema = Validator::isAssociative([
    'name' => Validator::isString()->required(),
    'age' => Validator::isInt()->min(0)->max(150),
    'email' => Validator::isString()->email()
]);

$userData = [
    'name' => 'John Doe',
    'age' => 30,
    'email' => 'john@example.com'
];

$validUser = $userSchema->validate($userData);
```

### Objects (stdClass)

```php
$configSchema = Validator::isObject([
    'debug' => Validator::isBool()->default(false),
    'timeout' => Validator::isInt()->min(1)->default(30)
]);

$config = new stdClass();
$config->debug = true;
$config->timeout = 60;

$validConfig = $configSchema->validate($config);
```

### Plain Arrays

```php
$numbersValidator = Validator::isArray(
    Validator::isInt()->min(0) // Each item must be a non-negative integer
);

$numbers = [1, 2, 3, 4, 5];
$validNumbers = $numbersValidator->validate($numbers);
```

## Error Handling

Validation errors are collected comprehensively:

```php
$validator = Validator::isString()
    ->required()
    ->minLength(5)
    ->email();

[$valid, $data, $errors] = $validator->tryValidate('ab');

// $errors might contain:
// [
//     'Value must be at least 5 characters long',
//     'Value must be a valid email address'
// ]
```

## Method Chaining

All validator methods return the validator instance, enabling fluent chaining:

```php
$complexValidator = Validator::isString()
    ->required()
    ->minLength(8)
    ->maxLength(100)
    ->pattern('/^[A-Za-z0-9]+$/')
    ->satisfies(
        fn($value) => !in_array(strtolower($value), ['password', '123456']),
        'Password cannot be a common weak password'
    );
```

## Data Transformations

Transform and process data after successful validation using the type-aware transformation system.

### Basic Transformations

```php
// Type-preserving transformations with pipe()
$name = Validator::isString()
    ->pipe('trim', 'strtoupper')  // Multiple string operations
    ->validate('  john doe  '); // Returns: "JOHN DOE"

// Type-changing transformations with transform()
$count = Validator::isString()
    ->transform(fn($v) => explode(',', $v)) // String → Array
    ->transform('count')                    // Array → Int
    ->validate('a,b,c'); // Returns: 3
```

### When to Use Each Method

- **Use `pipe()`** for same-type operations (string → string, array → array)
- **Use `transform()`** for type changes (string → array, array → int)

```php
// Correct usage
$result = Validator::isArray()
    ->pipe('array_unique', 'array_reverse')    // Array operations (same type)
    ->transform(fn($v) => implode(',', $v))    // Array → String (type change)
    ->pipe('trim', 'strtoupper')               // String operations (same type)
    ->validate(['a', 'b', 'a']); // Returns: "A,B"
```

## Next Steps

- [Core Concepts](core-concepts.md) -- Understand the architecture
- [String Validation Guide](../guides/string-validation.md) -- Detailed string validation
- [Numeric Validation Guide](../guides/numeric-validation.md) -- Integer and float validation
- [Custom Validation Guide](../guides/custom-validation.md) -- Create your own validation rules
