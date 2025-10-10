# Custom Validation Guide

The Lemmon Validator allows you to add custom validation logic using the `satisfies()` method. This is perfect for business rules, complex validation logic, and context-aware validation that built-in validators can't handle.

## Basic Custom Validation

### Simple Custom Rule

```php
use Lemmon\Validator;

$validator = Validator::isString()->satisfies(
    function ($value) {
        return strlen($value) > 0 && ctype_alpha($value);
    },
    'Value must contain only alphabetic characters'
);

$result = $validator->validate('Hello'); // ✅ Valid
// $validator->validate('Hello123'); // ❌ ValidationException
```

### Using Arrow Functions

For simple validations, arrow functions provide cleaner syntax:

```php
$positiveNumberValidator = Validator::isInt()->satisfies(
    fn($value) => $value > 0,
    'Number must be positive'
);

$evenNumberValidator = Validator::isInt()->satisfies(
    fn($value) => $value % 2 === 0,
    'Number must be even'
);
```

## Context-Aware Validation

Custom validators receive three parameters: `$value`, `$key`, and `$input`, enabling sophisticated validation logic.

### Understanding the Parameters

```php
$contextValidator = Validator::isString()->satisfies(
    function ($value, $key, $input) {
        // $value - the current field value being validated
        // $key - the field name (in schema validation) or null
        // $input - the complete input data being validated or null

        return true; // Your validation logic here
    },
    'Custom validation message'
);
```

### Password Confirmation Example

A classic use case for context-aware validation:

```php
$passwordConfirmValidator = Validator::isString()->satisfies(
    function ($value, $key, $input) {
        // Ensure password confirmation matches the password field
        return isset($input['password']) && $value === $input['password'];
    },
    'Password confirmation must match the password'
);

$registrationSchema = Validator::isAssociative([
    'password' => Validator::isString()
        ->required()
        ->minLength(8),
    'password_confirm' => $passwordConfirmValidator->required()
]);

$validData = $registrationSchema->validate([
    'password' => 'secretpassword123',
    'password_confirm' => 'secretpassword123'
]); // ✅ Valid

// This would fail:
// $registrationSchema->validate([
//     'password' => 'secretpassword123',
//     'password_confirm' => 'differentpassword'
// ]); // ❌ ValidationException
```

### Field Dependency Validation

Validate fields based on other field values:

```php
$conditionalValidator = Validator::isString()->satisfies(
    function ($value, $key, $input) {
        // If account type is 'business', company name is required
        if (isset($input['account_type']) && $input['account_type'] === 'business') {
            return !empty($value);
        }
        return true; // Optional for non-business accounts
    },
    'Company name is required for business accounts'
);

$accountSchema = Validator::isAssociative([
    'account_type' => Validator::isString()
        ->required()
        ->oneOf(['personal', 'business']),
    'company_name' => $conditionalValidator
]);
```

### Cross-Field Validation

Ensure consistency between related fields:

```php
$endDateValidator = Validator::isString()->date()->satisfies(
    function ($value, $key, $input) {
        if (isset($input['start_date'])) {
            $startDate = new DateTime($input['start_date']);
            $endDate = new DateTime($value);
            return $endDate >= $startDate;
        }
        return true;
    },
    'End date must be after start date'
);

$eventSchema = Validator::isAssociative([
    'start_date' => Validator::isString()->date()->required(),
    'end_date' => $endDateValidator->required()
]);
```

## Business Logic Validation

### Custom Format Validation

```php
$productCodeValidator = Validator::isString()->satisfies(
    function ($value) {
        // Product code: 3 letters + hyphen + 4 digits + check digit
        if (!preg_match('/^[A-Z]{3}-\d{4}\d$/', $value)) {
            return false;
        }

        // Validate check digit (simple algorithm)
        $digits = substr($value, 4, 4);
        $checkDigit = (int) substr($value, -1);
        $calculatedCheck = array_sum(str_split($digits)) % 10;

        return $checkDigit === $calculatedCheck;
    },
    'Invalid product code format or check digit'
);

$code = $productCodeValidator->validate('ABC-12347'); // ✅ Valid (1+2+3+4=10, 10%10=0, but check digit is 7)
```

### Database Uniqueness Check

```php
class UserValidator
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function createUniqueEmailValidator(): StringValidator
    {
        return Validator::isString()
            ->email()
            ->satisfies(
                function ($email) {
                    $existingUser = $this->database->findUserByEmail($email);
                    return $existingUser === null;
                },
                'Email address is already registered'
            );
    }
}

// Usage
$userValidator = new UserValidator($database);
$emailValidator = $userValidator->createUniqueEmailValidator();
```

### Complex Business Rules

```php
$discountValidator = Validator::isFloat()->satisfies(
    function ($discount, $key, $input) {
        $orderTotal = $input['order_total'] ?? 0;
        $customerTier = $input['customer_tier'] ?? 'bronze';

        // Maximum discount based on customer tier
        $maxDiscounts = [
            'bronze' => 0.05,   // 5%
            'silver' => 0.10,   // 10%
            'gold' => 0.15,     // 15%
            'platinum' => 0.25  // 25%
        ];

        $maxDiscount = $maxDiscounts[$customerTier] ?? 0;
        $maxDiscountAmount = $orderTotal * $maxDiscount;

        return $discount <= $maxDiscountAmount;
    },
    'Discount exceeds maximum allowed for customer tier'
);

$orderSchema = Validator::isAssociative([
    'customer_tier' => Validator::isString()->oneOf(['bronze', 'silver', 'gold', 'platinum']),
    'order_total' => Validator::isFloat()->positive()->required(),
    'discount_amount' => $discountValidator
]);
```

## Chaining Custom Validations

You can chain multiple custom validations:

```php
$strongPasswordValidator = Validator::isString()
    ->minLength(8)
    ->satisfies(
        fn($value) => preg_match('/[A-Z]/', $value),
        'Password must contain at least one uppercase letter'
    )
    ->satisfies(
        fn($value) => preg_match('/[a-z]/', $value),
        'Password must contain at least one lowercase letter'
    )
    ->satisfies(
        fn($value) => preg_match('/\d/', $value),
        'Password must contain at least one number'
    )
    ->satisfies(
        fn($value) => preg_match('/[!@#$%^&*]/', $value),
        'Password must contain at least one special character (!@#$%^&*)'
    )
    ->satisfies(
        fn($value) => !preg_match('/(.)\1{2,}/', $value),
        'Password cannot contain more than 2 consecutive identical characters'
    );
```

## Error Collection

Custom validations participate in comprehensive error collection:

```php
$validator = Validator::isString()
    ->minLength(8)
    ->satisfies(fn($v) => false, 'Custom error 1')
    ->satisfies(fn($v) => false, 'Custom error 2');

[$valid, $data, $errors] = $validator->tryValidate('short');

// $errors will contain:
// [
//     'Value must be at least 8 characters long',
//     'Custom error 1',
//     'Custom error 2'
// ]
```

## Advanced Patterns

### Validation with External Services

```php
$emailDeliverabilityValidator = Validator::isString()
    ->email()
    ->satisfies(
        function ($email) {
            // Check with email verification service
            $verificationService = new EmailVerificationService();
            $result = $verificationService->verify($email);

            return $result->isDeliverable() && !$result->isDisposable();
        },
        'Email address is not deliverable or is a disposable email'
    );
```

### Async Validation (with Promises/Futures)

```php
// Note: This is a conceptual example - the library currently doesn't support async
$asyncValidator = Validator::isString()
    ->satisfies(
        function ($value) {
            // In a real async implementation, this would return a Promise
            $apiResponse = $this->httpClient->get("/validate/{$value}");
            return $apiResponse->getStatusCode() === 200;
        },
        'Value failed remote validation'
    );
```

### Conditional Validation Logic

```php
$conditionalValidator = Validator::isString()->satisfies(
    function ($value, $key, $input) {
        $validationMode = $input['validation_mode'] ?? 'strict';

        switch ($validationMode) {
            case 'strict':
                return preg_match('/^[A-Z][a-z]+$/', $value); // PascalCase
            case 'relaxed':
                return ctype_alpha($value); // Any letters
            case 'permissive':
                return true; // Accept anything
            default:
                return false;
        }
    },
    'Value format depends on validation mode'
);
```

## Testing Custom Validators

### Unit Testing Custom Validation Logic

```php
use PHPUnit\Framework\TestCase;

class CustomValidatorTest extends TestCase
{
    public function testPasswordConfirmationValidator()
    {
        $validator = Validator::isString()->satisfies(
            function ($value, $key, $input) {
                return isset($input['password']) && $value === $input['password'];
            },
            'Password confirmation must match'
        );

        // Test with matching passwords
        $schema = Validator::isAssociative([
            'password' => Validator::isString(),
            'password_confirm' => $validator
        ]);

        $validData = $schema->validate([
            'password' => 'secret123',
            'password_confirm' => 'secret123'
        ]);

        $this->assertEquals('secret123', $validData['password_confirm']);

        // Test with non-matching passwords
        $this->expectException(ValidationException::class);
        $schema->validate([
            'password' => 'secret123',
            'password_confirm' => 'different'
        ]);
    }
}
```

## Best Practices

1. **Keep It Simple**: Custom validators should focus on one specific rule
2. **Provide Clear Messages**: Error messages should be actionable and specific
3. **Handle Edge Cases**: Consider null values, empty strings, and invalid types
4. **Performance**: Avoid expensive operations in frequently-used validators
5. **Testability**: Write unit tests for complex custom validation logic

### Good Custom Validator

```php
$goodValidator = Validator::isString()->satisfies(
    function ($value, $key, $input) {
        // Clear, single responsibility
        // Handles edge cases
        // Fast execution
        return is_string($value) && str_word_count($value) <= 100;
    },
    'Text must not exceed 100 words' // Clear, actionable message
);
```

### Avoid This

```php
$badValidator = Validator::isString()->satisfies(
    function ($value) {
        // Multiple responsibilities, unclear logic, no edge case handling
        return strlen($value) > 5 && preg_match('/complex/', $value) &&
               file_get_contents('http://api.example.com/validate') === 'ok';
    },
    'Invalid' // Vague message
);
```

## Next Steps

- 🧩 [Array Validation Guide](array-validation.md) - Logical combinators and complex rules
- ❌ [Error Handling Guide](error-handling.md) - Working with validation errors
- 📚 [API Reference - Validator Factory](../api-reference/validator-factory.md) - Complete `satisfies()` reference
- 💡 [Form Validation Examples](../examples/form-validation.md) - See custom validation in action
