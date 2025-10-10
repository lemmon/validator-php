# Object & Schema Validation

This guide covers validation of structured data using `AssociativeValidator` (for associative arrays) and `ObjectValidator` (for stdClass objects), both supporting schema-based validation.

## Table of Contents

- [Associative Array Validation](#associative-array-validation)
- [Object Validation](#object-validation)
- [Schema Definition](#schema-definition)
- [Type Coercion](#type-coercion)
- [Advanced Features](#advanced-features)
- [Common Patterns](#common-patterns)
- [Error Handling](#error-handling)

## Associative Array Validation

### Basic Associative Array Validation

```php
use Lemmon\Validator;

// Simple associative array without schema
$validator = Validator::isAssociative();
$result = $validator->validate(['key' => 'value']);
// Result: ['key' => 'value']

$result = $validator->validate([]);
// Result: []

// Null is allowed for optional validators
$result = $validator->validate(null);
// Result: null
```

### Schema-Based Validation

```php
// Define schema for user data
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

$result = $userSchema->validate($userData);
// Result: ['name' => 'John Doe', 'age' => 30, 'email' => 'john@example.com']
```

## Object Validation

### Basic Object Validation

```php
// Validate stdClass objects with schema
$objectSchema = Validator::isObject([
    'name' => Validator::isString(),
    'age' => Validator::isInt()->coerce()
]);

$input = (object)[
    'name' => 'John Doe',
    'age' => '42' // String will be coerced to int
];

$result = $objectSchema->validate($input);
// Result: stdClass object with name='John Doe', age=42
```

### Object Creation from Arrays

```php
// Coerce associative array to stdClass object
$objectSchema = Validator::isObject([
    'name' => Validator::isString(),
    'age' => Validator::isInt()
])->coerce();

$input = [
    'name' => 'Jane Doe',
    'age' => 30
];

$result = $objectSchema->validate($input);
// Result: stdClass object with name='Jane Doe', age=30
```

## Schema Definition

### Required Fields

```php
$schema = Validator::isAssociative([
    'required_field' => Validator::isString()->required(),
    'optional_field' => Validator::isString() // Optional by default
]);

// Valid - optional field can be missing
$result = $schema->validate(['required_field' => 'value']);

// Invalid - required field missing
$schema->validate(['optional_field' => 'value']); // Throws ValidationException
```

### Default Values

```php
$schema = Validator::isAssociative([
    'name' => Validator::isString()->required(),
    'status' => Validator::isString()->default('active'),
    'priority' => Validator::isInt()->default(1)
]);

$input = ['name' => 'John'];
$result = $schema->validate($input);
// Result: ['name' => 'John', 'status' => 'active', 'priority' => 1]
```

### Nested Schemas

```php
$userSchema = Validator::isAssociative([
    'name' => Validator::isString()->required(),
    'profile' => Validator::isAssociative([
        'bio' => Validator::isString(),
        'website' => Validator::isString()->url()
    ]),
    'preferences' => Validator::isObject([
        'theme' => Validator::isString()->oneOf(['light', 'dark'])->default('light'),
        'notifications' => Validator::isBool()->default(true)
    ])
]);

$userData = [
    'name' => 'John Doe',
    'profile' => [
        'bio' => 'Software developer',
        'website' => 'https://johndoe.dev'
    ],
    'preferences' => [
        'theme' => 'dark',
        'notifications' => false
    ]
];

$result = $userSchema->validate($userData);
```

## Type Coercion

### Individual Field Coercion

```php
$schema = Validator::isAssociative([
    'id' => Validator::isInt()->coerce(), // String to int
    'active' => Validator::isBool()->coerce(), // String to bool
    'price' => Validator::isFloat()->coerce() // String to float
]);

$input = [
    'id' => '123',
    'active' => 'true',
    'price' => '19.99'
];

$result = $schema->validate($input);
// Result: ['id' => 123, 'active' => true, 'price' => 19.99]
```

### Global Coercion with coerceAll()

```php
$schema = Validator::isAssociative([
    'level' => Validator::isInt()->oneOf([3, 5, 8]),
    'override' => Validator::isBool()
])->coerceAll(); // Enables coercion for all fields

$input = [
    'level' => '5', // String coerced to int
    'override' => 'false' // String coerced to bool
];

$result = $schema->validate($input);
// Result: ['level' => 5, 'override' => false]
```

### Object-Array Coercion

```php
// AssociativeValidator can coerce objects to arrays
$arraySchema = Validator::isAssociative([
    'name' => Validator::isString()
])->coerce();

$object = new stdClass();
$object->name = 'John Doe';

$result = $arraySchema->validate($object);
// Result: ['name' => 'John Doe']

// ObjectValidator can coerce arrays to objects
$objectSchema = Validator::isObject([
    'name' => Validator::isString()
])->coerce();

$array = ['name' => 'Jane Doe'];
$result = $objectSchema->validate($array);
// Result: stdClass object with name='Jane Doe'
```

## Advanced Features

### Conditional Validation

```php
$schema = Validator::isAssociative([
    'type' => Validator::isString()->oneOf(['user', 'admin'])->required(),
    'permissions' => Validator::isArray()->satisfies(
        function ($value, $key, $input) {
            // Only require permissions for admin users
            if ($input['type'] === 'admin') {
                return !empty($value);
            }
            return true;
        },
        'Admin users must have permissions'
    )
]);
```

### Complex Validation Rules

```php
$productSchema = Validator::isAssociative([
    'name' => Validator::isString()->required()->minLength(3),
    'price' => Validator::isFloat()->positive()->multipleOf(0.01),
    'category' => Validator::isString()->oneOf(['electronics', 'clothing', 'books']),
    'tags' => Validator::isArray()->items(Validator::isString()),
    'metadata' => Validator::isAssociative([
        'weight' => Validator::isFloat()->positive(),
        'dimensions' => Validator::isAssociative([
            'length' => Validator::isFloat()->positive(),
            'width' => Validator::isFloat()->positive(),
            'height' => Validator::isFloat()->positive()
        ])
    ])
]);
```

## Common Patterns

### API Request Validation

```php
// Validate API request payload
$createUserRequest = Validator::isAssociative([
    'username' => Validator::isString()
        ->required()
        ->minLength(3)
        ->maxLength(20)
        ->pattern('/^[a-zA-Z0-9_]+$/', 'Username can only contain letters, numbers, and underscores'),
    'email' => Validator::isString()->email()->required(),
    'password' => Validator::isString()->minLength(8)->required(),
    'profile' => Validator::isAssociative([
        'first_name' => Validator::isString()->required(),
        'last_name' => Validator::isString()->required(),
        'bio' => Validator::isString()->maxLength(500)
    ])
]);
```

### Configuration Validation

```php
// Application configuration schema
$configSchema = Validator::isAssociative([
    'app' => Validator::isAssociative([
        'name' => Validator::isString()->required(),
        'debug' => Validator::isBool()->default(false),
        'timezone' => Validator::isString()->default('UTC')
    ]),
    'database' => Validator::isAssociative([
        'host' => Validator::isString()->required(),
        'port' => Validator::isInt()->min(1)->max(65535)->default(3306),
        'username' => Validator::isString()->required(),
        'password' => Validator::isString()->required(),
        'database' => Validator::isString()->required()
    ]),
    'cache' => Validator::isAssociative([
        'driver' => Validator::isString()->oneOf(['redis', 'memcached', 'file'])->default('file'),
        'ttl' => Validator::isInt()->positive()->default(3600)
    ])
]);
```

### Form Data Validation

```php
// Contact form validation
$contactFormSchema = Validator::isAssociative([
    'name' => Validator::isString()->required()->trim(),
    'email' => Validator::isString()->email()->required(),
    'subject' => Validator::isString()->required()->maxLength(100),
    'message' => Validator::isString()->required()->minLength(10)->maxLength(1000),
    'newsletter' => Validator::isBool()->default(false),
    'terms' => Validator::isBool()->satisfies(
        fn($value) => $value === true,
        'You must accept the terms and conditions'
    )
]);
```

## Error Handling

### Schema Validation Errors

```php
use Lemmon\ValidationException;

$schema = Validator::isAssociative([
    'name' => Validator::isString()->required(),
    'age' => Validator::isInt()->min(18)
]);

$input = [
    'age' => 16 // Invalid: too young
    // Missing required 'name' field
];

try {
    $schema->validate($input);
} catch (ValidationException $e) {
    print_r($e->getErrors());
    // Output:
    // [
    //     'name' => ['Value is required'],
    //     'age' => ['Value must be at least 18']
    // ]
}
```

### Nested Error Handling

```php
$nestedSchema = Validator::isAssociative([
    'user' => Validator::isAssociative([
        'profile' => Validator::isAssociative([
            'email' => Validator::isString()->email()->required()
        ])
    ])
]);

$input = [
    'user' => [
        'profile' => [
            'email' => 'invalid-email'
        ]
    ]
];

try {
    $nestedSchema->validate($input);
} catch (ValidationException $e) {
    print_r($e->getErrors());
    // Nested error structure reflects the schema structure
}
```

### Using tryValidate for Graceful Error Handling

```php
$schema = Validator::isAssociative([
    'items' => Validator::isArray()->items(Validator::isInt())
]);

[$valid, $result, $errors] = $schema->tryValidate([
    'items' => ['1', 'invalid', '3']
]);

if (!$valid) {
    echo "Validation failed:\n";
    foreach ($errors as $field => $fieldErrors) {
        echo "$field: " . implode(', ', $fieldErrors) . "\n";
    }
} else {
    echo "Validation successful:\n";
    print_r($result);
}
```

## Advanced Examples

### Multi-Step Form Validation

```php
// Registration form with multiple steps
$step1Schema = Validator::isAssociative([
    'email' => Validator::isString()->email()->required(),
    'password' => Validator::isString()->minLength(8)->required(),
    'password_confirm' => Validator::isString()->satisfies(
        function ($value, $key, $input) {
            return isset($input['password']) && $value === $input['password'];
        },
        'Password confirmation must match password'
    )
]);

$step2Schema = Validator::isAssociative([
    'first_name' => Validator::isString()->required(),
    'last_name' => Validator::isString()->required(),
    'birth_date' => Validator::isString()->datetime('Y-m-d')->required()
]);

$step3Schema = Validator::isAssociative([
    'company' => Validator::isString(),
    'job_title' => Validator::isString(),
    'industry' => Validator::isString()->oneOf([
        'technology', 'finance', 'healthcare', 'education', 'other'
    ])
]);
```

### E-commerce Product Schema

```php
$productSchema = Validator::isAssociative([
    'basic_info' => Validator::isAssociative([
        'name' => Validator::isString()->required()->minLength(3)->maxLength(100),
        'description' => Validator::isString()->required()->maxLength(2000),
        'sku' => Validator::isString()->required()->pattern('/^[A-Z0-9-]+$/'),
        'brand' => Validator::isString()->required()
    ]),
    'pricing' => Validator::isAssociative([
        'price' => Validator::isFloat()->positive()->multipleOf(0.01)->required(),
        'sale_price' => Validator::isFloat()->positive()->multipleOf(0.01),
        'currency' => Validator::isString()->oneOf(['USD', 'EUR', 'GBP'])->default('USD')
    ]),
    'inventory' => Validator::isAssociative([
        'stock_quantity' => Validator::isInt()->min(0)->required(),
        'track_inventory' => Validator::isBool()->default(true),
        'allow_backorder' => Validator::isBool()->default(false)
    ]),
    'shipping' => Validator::isAssociative([
        'weight' => Validator::isFloat()->positive(),
        'dimensions' => Validator::isAssociative([
            'length' => Validator::isFloat()->positive(),
            'width' => Validator::isFloat()->positive(),
            'height' => Validator::isFloat()->positive()
        ]),
        'shipping_class' => Validator::isString()->oneOf(['standard', 'heavy', 'fragile'])
    ]),
    'seo' => Validator::isAssociative([
        'meta_title' => Validator::isString()->maxLength(60),
        'meta_description' => Validator::isString()->maxLength(160),
        'slug' => Validator::isString()->pattern('/^[a-z0-9-]+$/')
    ])
]);
```

## Next Steps

- Learn about [Custom Validation](custom-validation.md) for business logic
- Explore [Array Validation](array-validation.md) for complex array validation scenarios
- Check out [Error Handling](error-handling.md) for comprehensive error management
- See [Form Validation Examples](../examples/form-validation.md) for practical examples
