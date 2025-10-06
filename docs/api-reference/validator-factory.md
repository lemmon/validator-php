# Validator Factory API Reference

The `Validator` class serves as the main entry point and factory for creating validator instances. All validation begins with static methods from this class.

## Class Overview

```php
namespace Lemmon;

class Validator
{
    // Factory methods for creating specific validators
    public static function isString(): StringValidator
    public static function isInt(): IntValidator
    public static function isFloat(): FloatValidator
    public static function isArray(?FieldValidator $itemValidator = null): ArrayValidator
    public static function isAssociative(array $schema = []): AssociativeValidator
    public static function isObject(array $schema = []): ObjectValidator
    public static function isBool(): BoolValidator
}
```

## Factory Methods

### `isString(): StringValidator`

Creates a validator for string values.

```php
$validator = Validator::isString();
$result = $validator->validate('Hello, World!'); // Returns: 'Hello, World!'

// With string-specific methods
$emailValidator = Validator::isString()->email();
$urlValidator = Validator::isString()->url()->minLength(10);
```

**Returns:** `StringValidator` instance with string-specific validation methods.

**See Also:** [StringValidator API Reference](string-validator.md)

---

### `isInt(): IntValidator`

Creates a validator for integer values.

```php
$validator = Validator::isInt();
$result = $validator->validate(42); // Returns: 42 (int)

// With integer-specific constraints
$ageValidator = Validator::isInt()->min(0)->max(150);
$idValidator = Validator::isInt()->positive();
```

**Returns:** `IntValidator` instance with integer-specific validation methods.

**See Also:** [Numeric Validators API Reference](numeric-validators.md)

---

### `isFloat(): FloatValidator`

Creates a validator for floating-point number values.

```php
$validator = Validator::isFloat();
$result = $validator->validate(3.14159); // Returns: 3.14159 (float)

// With float-specific constraints
$priceValidator = Validator::isFloat()->positive()->multipleOf(0.01);
$percentageValidator = Validator::isFloat()->min(0.0)->max(100.0);
```

**Returns:** `FloatValidator` instance with float-specific validation methods.

**See Also:** [Numeric Validators API Reference](numeric-validators.md)

---

### `isArray(?FieldValidator $itemValidator = null): ArrayValidator`

Creates a validator for indexed arrays.

```php
// Simple array validation
$validator = Validator::isArray();
$result = $validator->validate([1, 2, 3]); // Returns: [1, 2, 3]

// Array with item validation
$numbersValidator = Validator::isArray(
    Validator::isInt()->positive()
);
$numbers = $numbersValidator->validate([1, 2, 3]); // Each item validated as positive int

// Array of email addresses
$emailsValidator = Validator::isArray()->items(
    Validator::isString()->email()
);
```

**Parameters:**
- `$itemValidator` (optional): `FieldValidator` instance to validate each array item

**Returns:** `ArrayValidator` instance with array-specific validation methods.

**See Also:** [Array Validation Guide](../guides/array-validation.md)

---

### `isAssociative(array $schema = []): AssociativeValidator`

Creates a validator for associative arrays (key-value pairs).

```php
// Simple associative array
$validator = Validator::isAssociative();
$result = $validator->validate(['key' => 'value']);

// With schema definition
$userValidator = Validator::isAssociative([
    'name' => Validator::isString()->required(),
    'email' => Validator::isString()->email(),
    'age' => Validator::isInt()->min(0)->max(150)
]);

$user = $userValidator->validate([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30
]);
```

**Parameters:**
- `$schema` (optional): Array mapping field names to `FieldValidator` instances

**Returns:** `AssociativeValidator` instance with schema validation capabilities.

**See Also:** [Structure Validators API Reference](structure-validators.md)

---

### `isObject(array $schema = []): ObjectValidator`

Creates a validator for `stdClass` objects.

```php
// Simple object validation
$validator = Validator::isObject();

$obj = new stdClass();
$obj->name = 'John';
$result = $validator->validate($obj); // Returns: stdClass object

// With schema definition
$configValidator = Validator::isObject([
    'debug' => Validator::isBool()->default(false),
    'timeout' => Validator::isInt()->min(1)->default(30),
    'host' => Validator::isString()->required()
]);

$config = new stdClass();
$config->debug = true;
$config->host = 'localhost';
$validConfig = $configValidator->validate($config);
```

**Parameters:**
- `$schema` (optional): Array mapping property names to `FieldValidator` instances

**Returns:** `ObjectValidator` instance with schema validation capabilities.

**See Also:** [Structure Validators API Reference](structure-validators.md)

---

### `isBool(): BoolValidator`

Creates a validator for boolean values.

```php
$validator = Validator::isBool();
$result = $validator->validate(true); // Returns: true (bool)

// With coercion for string inputs
$coercingValidator = Validator::isBool()->coerce();
$result = $coercingValidator->validate('true'); // Returns: true (bool)
$result = $coercingValidator->validate('1'); // Returns: true (bool)
$result = $coercingValidator->validate('false'); // Returns: false (bool)
```

**Returns:** `BoolValidator` instance with boolean-specific validation methods.

## Usage Patterns

### Basic Validation

```php
// Create validator and validate immediately
$email = Validator::isString()->email()->validate('user@example.com');
$age = Validator::isInt()->min(0)->validate(25);
$price = Validator::isFloat()->positive()->validate(19.99);
```

### Reusable Validators

```php
// Create once, use multiple times
$emailValidator = Validator::isString()->email();

$email1 = $emailValidator->validate('user1@example.com');
$email2 = $emailValidator->validate('user2@example.com');
$email3 = $emailValidator->validate('user3@example.com');
```

### Complex Schema Validation

```php
$orderValidator = Validator::isAssociative([
    'id' => Validator::isInt()->positive()->required(),
    'customer' => Validator::isAssociative([
        'name' => Validator::isString()->required()->minLength(2),
        'email' => Validator::isString()->email()->required(),
        'phone' => Validator::isString()->pattern('/^\+?[1-9]\d{1,14}$/')
    ])->required(),
    'items' => Validator::isArray(
        Validator::isAssociative([
            'product_id' => Validator::isInt()->positive()->required(),
            'quantity' => Validator::isInt()->min(1)->required(),
            'price' => Validator::isFloat()->positive()->required()
        ])
    )->required(),
    'total' => Validator::isFloat()->positive()->required(),
    'paid' => Validator::isBool()->default(false)
]);
```

### Method Chaining

All factory methods return validator instances that support fluent method chaining:

```php
$complexValidator = Validator::isString()
    ->required()
    ->minLength(8)
    ->maxLength(100)
    ->pattern('/^[A-Za-z0-9]+$/')
    ->addValidation(
        fn($value) => !in_array(strtolower($value), ['password', '123456']),
        'Value cannot be a common weak password'
    );
```

## Error Handling

All validators created by the factory support both validation methods:

### Exception-Based Validation

```php
try {
    $result = Validator::isString()->email()->validate('invalid-email');
} catch (ValidationException $e) {
    echo 'Validation failed: ' . implode(', ', $e->getErrors());
}
```

### Tuple-Based Validation

```php
[$valid, $data, $errors] = Validator::isString()->email()->tryValidate('invalid-email');

if ($valid) {
    echo 'Valid email: ' . $data;
} else {
    echo 'Errors: ' . implode(', ', $errors);
}
```

## Best Practices

### 1. Use Specific Types

Choose the most specific validator type for your data:

```php
// Good: Specific types
$age = Validator::isInt()->min(0)->max(150);
$price = Validator::isFloat()->positive()->multipleOf(0.01);
$email = Validator::isString()->email();

// Avoid: Generic validation
$age = Validator::isString(); // Age should be an integer
```

### 2. Create Reusable Validators

For commonly used validation patterns, create reusable validators:

```php
class CommonValidators
{
    public static function email(): StringValidator
    {
        return Validator::isString()->email();
    }

    public static function positiveInt(): IntValidator
    {
        return Validator::isInt()->positive();
    }

    public static function money(): FloatValidator
    {
        return Validator::isFloat()->positive()->multipleOf(0.01);
    }
}

// Usage
$email = CommonValidators::email()->validate('user@example.com');
$price = CommonValidators::money()->validate(19.99);
```

### 3. Schema Organization

For complex schemas, organize them logically:

```php
class UserSchemas
{
    public static function registration(): AssociativeValidator
    {
        return Validator::isAssociative([
            'personal_info' => self::personalInfo()->required(),
            'account_info' => self::accountInfo()->required(),
            'preferences' => self::preferences()->default([])
        ]);
    }

    private static function personalInfo(): AssociativeValidator
    {
        return Validator::isAssociative([
            'first_name' => Validator::isString()->required()->minLength(1),
            'last_name' => Validator::isString()->required()->minLength(1),
            'email' => Validator::isString()->email()->required()
        ]);
    }

    private static function accountInfo(): AssociativeValidator
    {
        return Validator::isAssociative([
            'username' => Validator::isString()->required()->minLength(3),
            'password' => Validator::isString()->required()->minLength(8)
        ]);
    }

    private static function preferences(): AssociativeValidator
    {
        return Validator::isAssociative([
            'theme' => Validator::isString()->oneOf(['light', 'dark'])->default('light'),
            'notifications' => Validator::isBool()->default(true)
        ]);
    }
}
```

---

### `anyOf(array $validators, ?string $message = null): FieldValidator`

Creates a validator that passes if ANY of the provided validators pass. Perfect for mixed-type validation.

```php
// Mixed type validation - accepts string, int, or float
$flexibleId = Validator::anyOf([
    Validator::isInt()->positive(),
    Validator::isString()->uuid(),
    Validator::isString()->pattern('/^[A-Z]{3}-\d{4}$/')
]);

$result1 = $flexibleId->validate(123); // ✅ Valid (positive int)
$result2 = $flexibleId->validate('550e8400-e29b-41d4-a716-446655440000'); // ✅ Valid (UUID)
$result3 = $flexibleId->validate('ABC-1234'); // ✅ Valid (custom pattern)

// Array of mixed types
$mixedArray = Validator::isArray()->items(
    Validator::anyOf([
        Validator::isString(),
        Validator::isInt(),
        Validator::isFloat()
    ])
);
```

**Parameters:**
- `$validators`: Array of `FieldValidator` instances, at least one must pass
- `$message` (optional): Custom error message if all validators fail

**Returns:** `FieldValidator` instance that accepts any type matching at least one validator.

---

### `allOf(array $validators, ?string $message = null): FieldValidator`

Creates a validator that passes if ALL of the provided validators pass. Useful for combining multiple constraints.

```php
// String that must satisfy multiple conditions
$strictString = Validator::allOf([
    Validator::isString()->minLength(5),
    Validator::isString()->maxLength(20),
    Validator::isString()->pattern('/^[A-Za-z]+$/'),
    Validator::isString()->addValidation(
        fn($value) => !in_array(strtolower($value), ['admin', 'root']),
        'Cannot be reserved word'
    )
]);

$result = $strictString->validate('HelloWorld'); // ✅ Valid (passes all conditions)

// Schema validation with combined constraints
$userSchema = Validator::isAssociative([
    'name' => Validator::allOf([
        Validator::isString()->required(),
        Validator::isString()->minLength(2),
        Validator::isString()->maxLength(50)
    ])
]);
```

**Parameters:**
- `$validators`: Array of `FieldValidator` instances that must all pass
- `$message` (optional): Custom error message if any validator fails

**Returns:** `FieldValidator` instance that requires all validators to pass.

---

### `not(FieldValidator $validator, ?string $message = null): FieldValidator`

Creates a validator that passes if the provided validator does NOT pass. Perfect for exclusion logic.

```php
// String that is NOT an email
$notEmail = Validator::not(
    Validator::isString()->email(),
    'Value must not be an email address'
);

$result1 = $notEmail->validate('hello world'); // ✅ Valid (not an email)
$result2 = $notEmail->validate(123); // ✅ Valid (not an email)

// User status that cannot be banned or suspended
$validStatus = Validator::not(
    Validator::isString()->oneOf(['banned', 'suspended']),
    'User cannot have banned or suspended status'
);

$result3 = $validStatus->validate('active'); // ✅ Valid
$result4 = $validStatus->validate('pending'); // ✅ Valid
```

**Parameters:**
- `$validator`: The `FieldValidator` instance that must fail
- `$message` (optional): Custom error message if the validator passes

**Returns:** `FieldValidator` instance that passes when the provided validator fails.

## See Also

- [FieldValidator API Reference](field-validator.md) - Base validator methods
- [StringValidator API Reference](string-validator.md) - String-specific methods
- [Numeric Validators API Reference](numeric-validators.md) - Integer and float methods
- [Structure Validators API Reference](structure-validators.md) - Array and object methods
- [Getting Started Guide](../getting-started/basic-usage.md) - Basic usage patterns
