# String Validation Guide

The `StringValidator` provides comprehensive validation for string data, including format validation, length constraints, and pattern matching.

## Basic String Validation

Create a string validator using the factory:

```php
use Lemmon\Validator;

$validator = Validator::isString();
$result = $validator->validate('Hello, World!'); // Returns: 'Hello, World!'
```

## Format Validators

### Email Validation

```php
$emailValidator = Validator::isString()->email();

// Valid emails
$email = $emailValidator->validate('user@example.com');
$email = $emailValidator->validate('test.email+tag@domain.co.uk');

// Custom error message
$customEmail = Validator::isString()->email('Please enter a valid email address');
```

### URL Validation

```php
$urlValidator = Validator::isString()->url();

// Valid URLs
$url = $urlValidator->validate('https://example.com');
$url = $urlValidator->validate('http://localhost:8080/path?query=value');
$url = $urlValidator->validate('ftp://files.example.com/file.txt');

// Custom message
$customUrl = Validator::isString()->url('Please provide a valid URL');
```

### UUID Validation

```php
$uuidValidator = Validator::isString()->uuid();

// Valid UUIDs (all versions supported)
$uuid = $uuidValidator->validate('550e8400-e29b-41d4-a716-446655440000');
$uuid = $uuidValidator->validate('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
```

### IP Address Validation

```php
$ipValidator = Validator::isString()->ip();

// Valid IP addresses (IPv4 and IPv6)
$ipv4 = $ipValidator->validate('192.168.1.1');
$ipv4 = $ipValidator->validate('10.0.0.1');
$ipv6 = $ipValidator->validate('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
$ipv6 = $ipValidator->validate('::1'); // localhost IPv6
```

### Date and DateTime Validation

```php
// DateTime validation (ISO 8601 format)
$datetimeValidator = Validator::isString()->datetime();
$datetime = $datetimeValidator->validate('2023-12-25T15:30:00Z');
$datetime = $datetimeValidator->validate('2023-12-25T15:30:00+02:00');

// Date validation (YYYY-MM-DD format)
$dateValidator = Validator::isString()->date();
$date = $dateValidator->validate('2023-12-25');
$date = $dateValidator->validate('2023-01-01');
```

## Length Constraints

### Minimum Length

```php
$minLengthValidator = Validator::isString()->minLength(3);

$result = $minLengthValidator->validate('Hello'); // Valid (5 chars)
// $minLengthValidator->validate('Hi'); // ❌ ValidationException (2 chars)

// Custom message
$customMin = Validator::isString()->minLength(8, 'Password must be at least 8 characters');
```

### Maximum Length

```php
$maxLengthValidator = Validator::isString()->maxLength(10);

$result = $maxLengthValidator->validate('Short'); // Valid (5 chars)
// $maxLengthValidator->validate('This is too long'); // ❌ ValidationException (16 chars)
```

### Exact Length

```php
$exactLengthValidator = Validator::isString()->length(5);

$result = $exactLengthValidator->validate('Hello'); // Valid (exactly 5 chars)
// $exactLengthValidator->validate('Hi'); // ❌ ValidationException (2 chars)
// $exactLengthValidator->validate('Too Long'); // ❌ ValidationException (8 chars)
```

### Combined Length Constraints

```php
$passwordValidator = Validator::isString()
    ->minLength(8, 'Password must be at least 8 characters')
    ->maxLength(128, 'Password must not exceed 128 characters');
```

## Pattern Matching

Use regular expressions for custom string patterns:

```php
// Alphanumeric only
$alphanumericValidator = Validator::isString()
    ->pattern('/^[A-Za-z0-9]+$/', 'Only letters and numbers allowed');

// Phone number (simple pattern)
$phoneValidator = Validator::isString()
    ->pattern('/^\+?[1-9]\d{1,14}$/', 'Invalid phone number format');

// Custom code format (2 letters + 4 digits)
$codeValidator = Validator::isString()
    ->pattern('/^[A-Z]{2}\d{4}$/', 'Code must be 2 uppercase letters followed by 4 digits');

$code = $codeValidator->validate('AB1234'); // Valid
// $codeValidator->validate('ab1234'); // ❌ ValidationException (lowercase)
// $codeValidator->validate('ABC123'); // ❌ ValidationException (wrong format)
```

## Form-Safe String Handling

### Empty String Nullification

The `nullifyEmpty()` method converts empty strings to `null`, which is crucial for form safety and preventing unintended empty string storage:

```php
// Basic nullification
$nameValidator = Validator::isString()->nullifyEmpty();
$result = $nameValidator->validate(''); // Returns: null (not '')
$result = $nameValidator->validate('John'); // Returns: 'John'

// Form-safe optional fields
$middleNameValidator = Validator::isString()
    ->nullifyEmpty() // Empty strings become null
    ->default('N/A'); // Use default for null values

$result = $middleNameValidator->validate(''); // Returns: 'N/A'
$result = $middleNameValidator->validate('James'); // Returns: 'James'
```

### Why This Matters for Forms

HTML forms often submit empty strings for unfilled fields. Without `nullifyEmpty()`, these become stored as empty strings in your database:

```php
// ❌ Dangerous: Empty strings stored as ''
$badValidator = Validator::isString();
$result = $badValidator->validate(''); // Returns: '' (empty string)

// Safe: Empty strings become null
$safeValidator = Validator::isString()->nullifyEmpty();
$result = $safeValidator->validate(''); // Returns: null

// Even better: With meaningful defaults
$defaultValidator = Validator::isString()
    ->nullifyEmpty()
    ->default('Not provided');
$result = $defaultValidator->validate(''); // Returns: 'Not provided'
```

### Form Validation Example

```php
$contactFormValidator = Validator::isAssociative([
    'name' => Validator::isString()
        ->required('Name is required')
        ->minLength(2, 'Name must be at least 2 characters'),

    'email' => Validator::isString()
        ->required('Email is required')
        ->email('Please provide a valid email address'),

    'company' => Validator::isString()
        ->nullifyEmpty() // Optional field: empty → null
        ->maxLength(100, 'Company name too long'),

    'phone' => Validator::isString()
        ->nullifyEmpty() // Optional field: empty → null
        ->pattern('/^\+?[1-9]\d{1,14}$/', 'Invalid phone number format'),

    'message' => Validator::isString()
        ->required('Message is required')
        ->minLength(10, 'Message must be at least 10 characters')
]);

// Form data with empty optional fields
$formData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'company' => '', // Empty string from form
    'phone' => '',   // Empty string from form
    'message' => 'Hello, I would like more information.'
];

$result = $contactFormValidator->validate($formData);
// Result:
// [
//     'name' => 'John Doe',
//     'email' => 'john@example.com',
//     'company' => null,  // Converted from empty string
//     'phone' => null,    // Converted from empty string
//     'message' => 'Hello, I would like more information.'
// ]
```

## Combining Validators

Chain multiple validation rules for comprehensive string validation:

```php
$comprehensiveValidator = Validator::isString()
    ->required()
    ->minLength(5)
    ->maxLength(50)
    ->email()
    ->satisfies(
        fn($email) => !str_ends_with($email, '.temp'),
        'Temporary email addresses are not allowed'
    );

// This validator ensures the string is:
// - Required (not null)
// - Between 5-50 characters
// - A valid email format
// - Not ending with '.temp'
```

## Real-World Examples

### User Registration Form

```php
$registrationSchema = Validator::isAssociative([
    'username' => Validator::isString()
        ->required()
        ->minLength(3)
        ->maxLength(20)
        ->pattern('/^[A-Za-z0-9_]+$/', 'Username can only contain letters, numbers, and underscores'),

    'email' => Validator::isString()
        ->required()
        ->email('Please enter a valid email address'),

    'password' => Validator::isString()
        ->required()
        ->minLength(8, 'Password must be at least 8 characters')
        ->pattern('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', 'Password must contain uppercase, lowercase, and number'),

    'website' => Validator::isString()
        ->url('Please enter a valid website URL') // Optional field
]);
```

### API Configuration

```php
$apiConfigSchema = Validator::isAssociative([
    'api_key' => Validator::isString()
        ->required()
        ->length(32, 'API key must be exactly 32 characters')
        ->pattern('/^[A-Za-z0-9]+$/', 'API key contains invalid characters'),

    'endpoint' => Validator::isString()
        ->required()
        ->url('API endpoint must be a valid URL'),

    'version' => Validator::isString()
        ->pattern('/^v\d+(\.\d+)*$/', 'Version must be in format v1, v1.0, v1.2.3, etc')
        ->default('v1'),

    'user_agent' => Validator::isString()
        ->minLength(1)
        ->maxLength(255)
        ->default('Lemmon-Validator/1.0')
]);
```

### Content Validation

```php
$articleValidator = Validator::isAssociative([
    'title' => Validator::isString()
        ->required()
        ->minLength(5, 'Title must be at least 5 characters')
        ->maxLength(100, 'Title must not exceed 100 characters'),

    'slug' => Validator::isString()
        ->required()
        ->pattern('/^[a-z0-9-]+$/', 'Slug can only contain lowercase letters, numbers, and hyphens')
        ->satisfies(
            fn($slug) => !str_starts_with($slug, '-') && !str_ends_with($slug, '-'),
            'Slug cannot start or end with a hyphen'
        ),

    'content' => Validator::isString()
        ->required()
        ->minLength(50, 'Article content must be at least 50 characters'),

    'tags' => Validator::isArray(
        Validator::isString()
            ->minLength(2)
            ->maxLength(20)
            ->pattern('/^[a-z0-9-]+$/', 'Tags can only contain lowercase letters, numbers, and hyphens')
    )
]);
```

## Error Handling

String validators collect all validation errors:

```php
$validator = Validator::isString()
    ->required()
    ->minLength(8)
    ->email();

[$valid, $data, $errors] = $validator->tryValidate('ab');

// $errors will contain:
// [
//     'Value must be at least 8 characters long',
//     'Value must be a valid email address'
// ]
```

## Performance Tips

1. **Reuse Validators**: Create validator instances once and reuse them
2. **Order Matters**: Put less expensive validations first (length before regex)
3. **Specific Patterns**: Use specific regex patterns rather than overly broad ones

```php
// Good: Reusable and efficient
$emailValidator = Validator::isString()->email();
$emails = array_map([$emailValidator, 'validate'], $emailList);

// Good: Cheap validation first
$efficientValidator = Validator::isString()
    ->minLength(3)          // Fast check first
    ->pattern('/complex/')  // Expensive regex last
    ->email();              // Built-in validation
```

## Next Steps

- [Numeric Validation Guide](numeric-validation.md) -- Learn about integer and float validation
- [Object & Schema Validation](object-validation.md) -- Handle complex nested structures
- [Custom Validation Guide](custom-validation.md) -- Create custom validation rules
- [API Reference - Validator Factory](../api-reference/validator-factory.md) -- Complete method reference
