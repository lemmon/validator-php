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
use Lemmon\Validator;

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
- `oneOf(array $values): static` - Restricts to specific values

### Custom Validation
- `addValidation(callable $rule, string $message): static` - Adds custom rules

### Logical Combinators
- `allOf(array $validators): static` - Must pass all validators (instance method)
- `anyOf(array $validators): static` - Must pass at least one validator (instance method)
- `not(FieldValidator $validator): static` - Must NOT pass the validator (instance method)

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
8. **Return Result** - Return validated/coerced value or errors

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
$validator->addValidation(
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

- 🔤 [String Validation Guide](../guides/string-validation.md) - Master string validation
- 🔢 [Numeric Validation Guide](../guides/numeric-validation.md) - Work with numbers
- 🏗️ [Object & Schema Validation](../guides/object-validation.md) - Handle complex structures
- ⚙️ [Custom Validation Guide](../guides/custom-validation.md) - Create custom business rules
