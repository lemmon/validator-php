# Core Concepts

Understanding these core concepts will help you make the most of the Lemmon Validator library.

## Architecture Overview

The library follows a clean, hierarchical architecture:

```
Validator (Factory)
    ↓
FieldValidator (Base Class)
    ↓
Specific Validators (StringValidator, IntValidator, etc.)
    ↓
Validation Rules (email(), min(), etc.)
```

## The Validator Factory

The `Validator` class is a factory that creates specific validator instances:

```php
use Lemmon\Validator\Validator;

// Factory methods create specific validators
$string = Validator::isString();    // → StringValidator
$int = Validator::isInt();          // → IntValidator
$float = Validator::isFloat();      // → FloatValidator
$array = Validator::isArray();      // → ArrayValidator
$assoc = Validator::isAssociative(); // → AssociativeValidator
$object = Validator::isObject();    // → ObjectValidator
$bool = Validator::isBool();        // → BoolValidator
```

## FieldValidator - The Base Class

All validators extend `FieldValidator`, which provides:

### Core Validation Methods
- `validate(mixed $value): mixed` - Throws exception on failure
- `tryValidate(mixed $value): array` - Returns `[bool, mixed, array]`

### Common Configuration
- `required(): static` - Makes the field mandatory
- `default(mixed $value): static` - Sets default value for null inputs
- `coerce(): static` - Enables automatic type conversion
- `oneOf(array $values): static` - Restricts to specific values (primitive validators only)

### Custom Validation
- `satisfies(callable|FieldValidator $rule, ?string $message = null): static` - Enhanced custom validation accepting validators or callables

### Instance Logical Combinators
- `satisfiesAll(array $validations): static` - Must pass all validators/callables (replaces `allOf()`)
- `satisfiesAny(array $validations): static` - Must pass at least one validator/callable (replaces `anyOf()`)
- `satisfiesNone(array $validations): static` - Must NOT pass any validator/callable (replaces `not()`)

**Note:** The old `allOf()`, `anyOf()`, and `not()` instance methods are deprecated but maintained for backward compatibility.

### Static Logical Combinators
- `Validator::allOf(array $validators)` - Creates validator that must pass all validators
- `Validator::anyOf(array $validators)` - Creates validator that must pass at least one validator
- `Validator::not(FieldValidator $validator)` - Creates validator that must NOT pass the validator

## Type-Specific Validators

Each validator handles a specific PHP type and provides relevant methods:

### StringValidator
```php
Validator::isString()
    ->minLength(3)      // Minimum length
    ->maxLength(100)    // Maximum length
    ->email()           // Email format
    ->url()             // URL format
    ->pattern('/regex/') // Custom regex
    // ... and more
```

### IntValidator & FloatValidator
Both share numeric constraints via `NumericConstraintsTrait`:

```php
Validator::isInt()
    ->min(0)            // Minimum value
    ->max(100)          // Maximum value
    ->positive()        // Must be > 0
    ->multipleOf(5)     // Must be divisible by 5

Validator::isFloat()
    ->min(0.0)          // Same methods for floats
    ->max(100.0)
    ->positive()
    ->multipleOf(0.01)  // Precision control
```

### ArrayValidator
```php
Validator::isArray()                    // Plain indexed array
Validator::isArray($itemValidator)      // With item validation
```

### AssociativeValidator & ObjectValidator
```php
Validator::isAssociative($schema)       // Associative array with schema
Validator::isObject($schema)            // stdClass object with schema
```

## Validation Flow

Understanding the validation flow helps debug and optimize your validators:

1. **Input Received** - Raw value passed to validator
2. **Required Check** - If required and value is null → error
3. **Default Application** - If optional and value is null → apply default
4. **Type Coercion** - If coercion enabled → attempt type conversion
5. **Type Validation** - Check if value matches expected type
6. **Built-in Rules** - Apply validator-specific rules (email, min, etc.)
7. **Custom Rules** - Apply user-defined validation functions
8. **Transformations** - Apply data transformations after successful validation
9. **Return Result** - Return validated/coerced/transformed value or errors

## Data Transformations

One of the library's most powerful features is the **type-aware transformation system** that allows you to process and transform data after validation.

### Two Types of Transformations

#### `transform()` - Type-Changing Transformations

The `transform()` method allows you to change the data type and updates the type context for subsequent operations:

```php
$result = Validator::isString()
    ->transform(fn($v) => explode(',', $v)) // String → Array (type changes)
    ->validate('a,b,c'); // Returns: ['a', 'b', 'c']
```

**Key Characteristics:**
- **Changes type context** - Updates internal type tracking
- **No type coercion** - Returns exactly what the transformer produces
- **Enables type switching** - Subsequent `pipe()` operations work with the new type

#### `pipe()` - Type-Preserving Transformations

The `pipe()` method applies multiple transformations while maintaining the current type context:

```php
$result = Validator::isString()
    ->pipe('trim', 'strtoupper', fn($v) => str_replace(' ', '-', $v))
    ->validate('  hello world  '); // Returns: "HELLO-WORLD"
```

**Key Characteristics:**
- **Preserves type context** - Maintains current type for consistency
- **Type-specific coercion** - Applies intelligent coercion based on current type
- **Multiple operations** - Accepts variadic arguments for clean chaining

### Type-Aware Transformation Chains

The revolutionary aspect is how these methods work together to create intelligent transformation chains:

```php
$result = Validator::isArray()
    ->pipe('array_unique', 'array_reverse')        // Array operations (maintains array type)
    ->transform(fn($v) => implode(',', $v))        // Array → String (type switches)
    ->pipe('trim', 'strtoupper')                   // String operations (works with string)
    ->transform('strlen')                          // String → Int (type switches again)
    ->validate(['a', 'b', 'a']); // Returns: 3
```

**What happens internally:**
1. **Initial type**: `indexed_array` (from `Validator::isArray()`)
2. **After `pipe()`**: Still `indexed_array`, but array is processed and reindexed
3. **After first `transform()`**: Type context switches to `string`
4. **After second `pipe()`**: Still `string`, string operations applied
5. **After final `transform()`**: Type context switches to `int`

### Smart Type Coercion

The `pipe()` method applies intelligent coercion based on the current type context:

```php
// Array pipe operations automatically reindex when needed
$result = Validator::isArray()
    ->pipe('array_filter', 'array_unique') // These might break indexing
    ->validate([1, '', 2, 1, 3]); // Returns: [1, 2, 3] (properly reindexed)

// Associative arrays preserve keys
$result = Validator::isAssociative()
    ->pipe(fn($v) => array_map('strtoupper', $v)) // Keys preserved
    ->validate(['name' => 'john', 'city' => 'paris']);
    // Returns: ['name' => 'JOHN', 'city' => 'PARIS']
```

### Integration with External Libraries

The transformation system seamlessly integrates with PHP's ecosystem:

```php
use Illuminate\Support\Str;

$slug = Validator::isString()
    ->pipe('trim', fn($v) => Str::lower($v))      // Laravel Str integration
    ->transform(fn($v) => Str::slug($v))          // Create URL slug
    ->validate('  Hello World  '); // Returns: "hello-world"

// Or with Laravel Collections
use Illuminate\Support\Collection;

$processed = Validator::isArray()
    ->transform(fn($v) => collect($v))            // Array → Collection
    ->transform(fn($c) => $c->unique()->values()) // Collection operations
    ->transform(fn($c) => $c->toArray())          // Collection → Array
    ->validate([1, 2, 2, 3]); // Returns: [1, 2, 3]
```

## Execution Order and Fluent API Contract

**Critical Concept**: The fluent API executes methods **in the exact order they are written**. This is fundamental to understanding how validation and transformation work together.

### Transformation Pipeline Order

All transformation-related methods (`pipe()`, `transform()`, `nullifyEmpty()`, `required()`) execute in their written order:

```php
// Execution order: trim → nullifyEmpty → required
$validator = Validator::isString()
    ->pipe('trim')        // 1. Trim whitespace
    ->nullifyEmpty()      // 2. Convert empty strings to null
    ->required();         // 3. Check if value is null (fails if so)

// Input: '    ' (spaces only)
$validator->validate('    '); // ❌ Throws "Value is required"
// Flow: '    ' → '' → null → ValidationException
```

### Order Matters - Different Results

The same methods in different orders produce different results:

```php
// Case 1: required() BEFORE nullifyEmpty()
$validator1 = Validator::isString()
    ->required()          // 1. Check if empty string is null (passes)
    ->nullifyEmpty();     // 2. Convert empty string to null

$result1 = $validator1->validate(''); // Returns: null

// Case 2: nullifyEmpty() BEFORE required()
$validator2 = Validator::isString()
    ->nullifyEmpty()      // 1. Convert empty string to null
    ->required();         // 2. Check if null value is null (fails)

$validator2->validate(''); // ❌ Throws "Value is required"
```

### Real-World Form Validation Example

This execution order is crucial for form validation:

```php
// Common form scenario: trim whitespace, handle empty fields, require non-empty
$nameValidator = Validator::isString()
    ->pipe('trim')                    // Remove leading/trailing spaces
    ->nullifyEmpty()                  // Empty strings become null
    ->required('Name is required');   // Reject null values

// Test cases:
$nameValidator->validate('John');     // Returns: "John"
$nameValidator->validate('  John  '); // Returns: "John" (trimmed)
$nameValidator->validate('');         // ❌ "Name is required"
$nameValidator->validate('    ');     // ❌ "Name is required" (trimmed to empty)
```

### Why This Design?

**Principle of Least Surprise**: The fluent API should work exactly as written. When you write:

```php
->pipe('trim')->nullifyEmpty()->required()
```

You expect: trim → nullify → require, and that's exactly what happens.

**Flexibility**: Different orders enable different behaviors for different use cases:

```php
// Scenario A: Allow empty after trimming (optional field)
->pipe('trim')->required()->nullifyEmpty()

// Scenario B: Require non-empty after trimming (required field)
->pipe('trim')->nullifyEmpty()->required()
```

## Error Collection Strategy

The library uses **comprehensive error collection** rather than fail-fast:

```php
$validator = Validator::isString()
    ->required()
    ->minLength(5)
    ->email();

// Instead of stopping at first error, collects ALL errors:
[$valid, $data, $errors] = $validator->tryValidate('ab');
// $errors = [
//     'Value must be at least 5 characters long',
//     'Value must be a valid email address'
// ]
```

This provides better user experience by showing all validation issues at once.

## Context-Aware Validation

Custom validation functions receive context about the validation:

```php
$validator->satisfies(
    function ($value, $key, $input) {
        // $value - the current field value
        // $key - the field name (in schema validation)
        // $input - the full input data (in schema validation)

        return $value !== $input['forbidden_value'] ?? null;
    },
    'Value cannot match the forbidden value'
);
```

## Type Coercion

Coercion attempts intelligent type conversion:

```php
// String to Int
$intValidator = Validator::isInt()->coerce();
$result = $intValidator->validate('123'); // Returns: 123 (int)

// Array to Object
$objectValidator = Validator::isObject()->coerce();
$result = $objectValidator->validate(['key' => 'value']); // Returns: stdClass

// Object to Array
$arrayValidator = Validator::isAssociative()->coerce();
$obj = new stdClass(); $obj->key = 'value';
$result = $arrayValidator->validate($obj); // Returns: ['key' => 'value']
```

### Form-Safe Empty String Handling

**BREAKING CHANGE (v0.6.0)**: The library now prioritizes real-world form safety over PHP's default type casting behavior.

#### The Problem with Traditional Coercion

In traditional PHP type casting, empty strings convert to "falsy" defaults:
- `(int) ''` → `0`
- `(float) ''` → `0.0`
- `(bool) ''` → `false`

This creates **dangerous scenarios** in real-world applications:

```php
// ❌ DANGEROUS: Traditional PHP behavior
$bankBalance = (int) $_POST['balance']; // Empty field becomes 0!
$itemQuantity = (int) $_POST['quantity']; // Empty field becomes 0!
$isActive = (bool) $_POST['active']; // Empty checkbox becomes false!
```

#### Form-Safe Solution

The Lemmon Validator treats empty strings as **"no value provided"** (`null`) rather than converting to potentially dangerous defaults:

```php
// SAFE: Lemmon Validator behavior
$validator = Validator::isInt()->coerce();
$bankBalance = $validator->validate(''); // Returns: null (not dangerous 0)

$validator = Validator::isFloat()->coerce();
$price = $validator->validate(''); // Returns: null (not dangerous 0.0)

$validator = Validator::isBool()->coerce();
$isActive = $validator->validate(''); // Returns: null (not dangerous false)
```

#### Real-World Form Scenarios

```php
// Form validation with safe empty string handling
$formValidator = Validator::isAssociative([
    'name' => Validator::isString()->required(), // Must be provided
    'age' => Validator::isInt()->coerce(),       // Empty → null (optional)
    'salary' => Validator::isFloat()->coerce(),  // Empty → null (safe!)
    'active' => Validator::isBool()->coerce(),   // Empty → null (optional)
]);

// Safe handling of empty form fields
$formData = [
    'name' => 'John Doe',
    'age' => '',      // Empty form field
    'salary' => '',   // Empty form field
    'active' => '',   // Empty checkbox
];

[$valid, $result, $errors] = $formValidator->tryValidate($formData);
// Result: ['name' => 'John Doe', 'age' => null, 'salary' => null, 'active' => null]
```

#### Migration Guide

If you need explicit zero defaults for empty fields, use `default()`:

```php
// If you need zero defaults (rare cases)
$quantity = Validator::isInt()
    ->coerce()
    ->default(0)  // Explicit zero default
    ->validate(''); // Returns: 0

// Better: Use nullifyEmpty() for explicit null conversion
$optional = Validator::isInt()
    ->nullifyEmpty() // Explicit empty → null
    ->validate(''); // Returns: null
```

## Schema Validation Deep Dive

Schema validation works recursively:

```php
$schema = Validator::isAssociative([
    'user' => Validator::isObject([
        'name' => Validator::isString()->required(),
        'contacts' => Validator::isArray(
            Validator::isAssociative([
                'type' => Validator::isString()->oneOf(['email', 'phone']),
                'value' => Validator::isString()->required()
            ])
        )
    ])
]);
```

Each level validates independently, and errors are collected hierarchically.

## Performance Considerations

- **Lazy Evaluation**: Validators are only executed when `validate()` or `tryValidate()` is called
- **Reusable Instances**: Validator instances are stateless and can be reused
- **Efficient Chaining**: Method chaining doesn't create new instances unnecessarily

```php
// Create once, use many times
$emailValidator = Validator::isString()->email();

$email1 = $emailValidator->validate('user1@example.com');
$email2 = $emailValidator->validate('user2@example.com');
// Same validator instance, no recreation overhead
```

## Next Steps

Now that you understand the core concepts:

- [String Validation Guide](../guides/string-validation.md) -- Master string validation
- [Numeric Validation Guide](../guides/numeric-validation.md) -- Work with numbers
- [Object & Schema Validation](../guides/object-validation.md) -- Handle complex structures
- [Custom Validation Guide](../guides/custom-validation.md) -- Create custom business rules
