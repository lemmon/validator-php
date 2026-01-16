# String Validation Guide

The `StringValidator` provides comprehensive validation for string data, including format validation, length constraints, and pattern matching.

## Basic String Validation

Create a string validator using the factory:

```php
use Lemmon\Validator\Validator;

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

The `uuid()` method accepts an optional `UuidVariant` enum to specify which UUID version to validate. The enum flag comes first, message parameter last:

```php
use Lemmon\Validator\UuidVariant;

// Any UUID version (default - accepts versions 1-7)
$uuidValidator = Validator::isString()->uuid();
$uuid = $uuidValidator->validate('550e8400-e29b-41d4-a716-446655440000'); // V4
$uuid = $uuidValidator->validate('6ba7b810-9dad-11d1-80b4-00c04fd430c8'); // V1
$uuid = $uuidValidator->validate('01890a5d-ac96-7748-b800-303132333435'); // V7

// Explicit UUID version 1 (time-based)
$v1Validator = Validator::isString()->uuid(UuidVariant::V1);
$uuid = $v1Validator->validate('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
// $v1Validator->validate('550e8400-e29b-41d4-a716-446655440000'); // ❌ ValidationException (V4)

// Explicit UUID version 4 (random)
$v4Validator = Validator::isString()->uuid(UuidVariant::V4);
$uuid = $v4Validator->validate('550e8400-e29b-41d4-a716-446655440000');
// $v4Validator->validate('6ba7b810-9dad-11d1-80b4-00c04fd430c8'); // ❌ ValidationException (V1)

// Explicit UUID version 7 (Unix timestamp-based, sortable)
$v7Validator = Validator::isString()->uuid(UuidVariant::V7);
$uuid = $v7Validator->validate('01890a5d-ac96-7748-b800-303132333435');
// $v7Validator->validate('550e8400-e29b-41d4-a716-446655440000'); // ❌ ValidationException (V4)

// Other versions: V2 (DCE Security), V3 (name-based MD5), V5 (name-based SHA-1)
$v2Validator = Validator::isString()->uuid(UuidVariant::V2);
$v3Validator = Validator::isString()->uuid(UuidVariant::V3);
$v5Validator = Validator::isString()->uuid(UuidVariant::V5);

// Custom message with variant (enum flag first, message last)
$customUuid = Validator::isString()->uuid(UuidVariant::V4, 'Must be UUID v4');
```

> **Note on UUID Implementation:** While UUID validation is provided as a built-in convenience method due to its widespread use, the library's primary focus is on core validation principles rather than implementing every possible validator. External libraries are encouraged because they stay current with newer UUID variants and versions, while the built-in implementation may fall behind as the library prioritizes core validation features over exhaustive validator coverage. For production applications requiring comprehensive UUID validation, parsing, or generation capabilities, consider using [`ramsey/uuid`](https://github.com/ramsey/uuid) via the `satisfies()` method:
>
> ```php
> use Ramsey\Uuid\Uuid;
>
> $advancedUuidValidator = Validator::isString()
>     ->satisfies(fn($v) => Uuid::isValid($v), 'Must be valid UUID');
> ```
>
> The built-in `uuid()` method provides basic format validation suitable for most use cases, but external libraries offer additional features like strict RFC compliance, parsing, generation, and support for the latest UUID specifications.

### IP Address Validation

The `ip()` method accepts an optional `IpVersion` enum to specify which IP version to validate. The enum flag comes first, message parameter last:

```php
use Lemmon\Validator\IpVersion;

// Any IP version (default - accepts both IPv4 and IPv6)
$ipValidator = Validator::isString()->ip();
$ipv4 = $ipValidator->validate('192.168.1.1');
$ipv6 = $ipValidator->validate('2001:0db8:85a3:0000:0000:8a2e:0370:7334');

// Explicit IPv4 only
$ipv4Validator = Validator::isString()->ip(IpVersion::IPv4);
$ipv4 = $ipv4Validator->validate('192.168.1.1');
$ipv4 = $ipv4Validator->validate('10.0.0.1');
// $ipv4Validator->validate('2001:0db8::1'); // ❌ ValidationException

// Explicit IPv6 only
$ipv6Validator = Validator::isString()->ip(IpVersion::IPv6);
$ipv6 = $ipv6Validator->validate('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
$ipv6 = $ipv6Validator->validate('::1'); // localhost IPv6
// $ipv6Validator->validate('192.168.1.1'); // ❌ ValidationException

// Custom message with version (enum flag first, message last)
$customIpv4 = Validator::isString()->ip(IpVersion::IPv4, 'Must be IPv4 format');
```

### Hostname Validation

```php
$hostnameValidator = Validator::isString()->hostname();

// Valid hostnames (includes domains, subdomains, and single labels)
$hostname = $hostnameValidator->validate('example.com');
$hostname = $hostnameValidator->validate('subdomain.example.com');
$hostname = $hostnameValidator->validate('test-domain.co.uk');
$hostname = $hostnameValidator->validate('localhost'); // Single label allowed

// Custom message
$customHostname = Validator::isString()->hostname('Please provide a valid hostname');
```

### Domain Validation

```php
$domainValidator = Validator::isString()->domain();

// Valid domains (requires at least one dot)
$domain = $domainValidator->validate('example.com');
$domain = $domainValidator->validate('subdomain.example.com');
$domain = $domainValidator->validate('test-domain.co.uk');

// Invalid: single labels (no dot)
// $domainValidator->validate('localhost'); // ❌ ValidationException

// Custom message
$customDomain = Validator::isString()->domain('Please provide a valid domain name');
```

**Note:** `domain()` is stricter than `hostname()` - it requires at least one dot, rejecting single-label hostnames like `localhost` or `server1`.

### Time Validation

```php
$timeValidator = Validator::isString()->time();

// Valid times (HH:MM format)
$time = $timeValidator->validate('12:30');
$time = $timeValidator->validate('00:00');
$time = $timeValidator->validate('23:59');

// Valid times (HH:MM:SS format)
$time = $timeValidator->validate('12:30:45');
$time = $timeValidator->validate('23:59:59');

// Custom message
$customTime = Validator::isString()->time('Please enter a valid time');
```

### Base64 Validation

The `base64()` method accepts an optional `Base64Variant` enum to specify which Base64 variant to validate. The enum flag comes first, message parameter last:

```php
use Lemmon\Validator\Base64Variant;

// Standard Base64 (default - uses +, /, and = padding)
$standardValidator = Validator::isString()->base64();
$base64 = $standardValidator->validate('SGVsbG8gV29ybGQ='); // "Hello World"
$base64 = $standardValidator->validate('dGVzdA=='); // "test"
$base64 = $standardValidator->validate('YWJj'); // "abc" (no padding)

// URL-safe Base64 (uses -, _, and accepts both variants)
$urlSafeValidator = Validator::isString()->base64(Base64Variant::UrlSafe);
$base64 = $urlSafeValidator->validate('SGVsbG8gV29ybGQ'); // URL-safe format
$base64 = $urlSafeValidator->validate('SGVsbG8gV29ybGQ='); // Standard format also accepted

// Any variant (accepts both standard and URL-safe)
$anyValidator = Validator::isString()->base64(Base64Variant::Any);
$base64 = $anyValidator->validate('SGVsbG8gV29ybGQ='); // Standard
$base64 = $anyValidator->validate('SGVsbG8gV29ybGQ'); // URL-safe

// Custom message with variant (enum flag first, message last)
$customBase64 = Validator::isString()->base64(Base64Variant::Standard, 'Must be valid Base64');
```

### Hexadecimal Validation

```php
$hexValidator = Validator::isString()->hex();

// Valid hex strings
$hex = $hexValidator->validate('deadbeef');
$hex = $hexValidator->validate('DEADBEEF');
$hex = $hexValidator->validate('1234567890abcdef');
$hex = $hexValidator->validate('a1b2c3');

// Custom message
$customHex = Validator::isString()->hex('Must be a hexadecimal string');
```

### Date and DateTime Validation

```php
// DateTime validation (default format: Y-m-d\TH:i:s)
$datetimeValidator = Validator::isString()->datetime();
$datetime = $datetimeValidator->validate('2023-12-25T15:30:00');

// DateTime validation with timezone offsets
$offsetValidator = Validator::isString()->datetime('Y-m-d\TH:i:sP');
$datetime = $offsetValidator->validate('2023-12-25T15:30:00+02:00');

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

### Length Between Bounds

Use `between(min, max)` to validate that a string's length falls within an inclusive range. This provides a unified error message showing both bounds:

```php
$validator = Validator::isString()->between(3, 8);
$validator->validate('Hello'); // Valid
// $validator->validate('Hi'); // ❌ ValidationException: "Value must be between 3 and 8 characters long"
// $validator->validate('Too long'); // ❌ ValidationException: "Value must be between 3 and 8 characters long"

// Custom error message
$customBetween = Validator::isString()->between(3, 8, 'Length must be 3-8 characters');
```

### Non-Empty Strings

Use `notEmpty()` as a clearer alternative to `minLength(1)` when you only want to reject empty strings.
By default it skips `null`; add `required()` if `null` should fail.

```php
$validator = Validator::isString()->notEmpty();

$validator->validate('Hello'); // Valid
// $validator->validate(''); // ❌ ValidationException
```

If you want to treat whitespace-only input as empty, trim first:

```php
$validator = Validator::isString()->pipe('trim')->notEmpty();
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

### Regex Alias

The `regex()` method is an alias for `pattern()` and works identically:

```php
// Both methods are equivalent
$patternValidator = Validator::isString()->pattern('/^\d{3}$/');
$regexValidator = Validator::isString()->regex('/^\d{3}$/');

// Both validate the same way
$patternValidator->validate('123'); // Valid
$regexValidator->validate('123'); // Valid
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

    'tags' => Validator::isArray()->items(
        Validator::isString()
            ->minLength(2)
            ->maxLength(20)
            ->pattern('/^[a-z0-9-]+$/', 'Tags can only contain lowercase letters, numbers, and hyphens')
    )
]);
```

## Error Handling

String validators fail fast per rule:

```php
$validator = Validator::isString()
    ->required()
    ->minLength(8)
    ->email();

[$valid, $data, $errors] = $validator->tryValidate('ab');

// $errors will contain:
// [
//     'Value must be at least 8 characters long'
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
