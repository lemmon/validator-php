# Form Validation Examples

This guide demonstrates how to use the Lemmon Validator for common form validation scenarios, from simple contact forms to complex multi-step registration processes.

## Basic Contact Form

### HTML Form

```html
<form method="POST" action="/contact">
    <div>
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
    </div>

    <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
    </div>

    <div>
        <label for="subject">Subject:</label>
        <input type="text" id="subject" name="subject">
    </div>

    <div>
        <label for="message">Message:</label>
        <textarea id="message" name="message" required></textarea>
    </div>

    <button type="submit">Send Message</button>
</form>
```

### PHP Validation

```php
<?php
use Lemmon\Validator;
use Lemmon\ValidationException;

class ContactFormValidator
{
    private $validator;

    public function __construct()
    {
        $this->validator = Validator::isAssociative([
            'name' => Validator::isString()
                ->pipe('trim')                    // Clean whitespace first
                ->nullifyEmpty()                  // Handle empty fields
                ->required('Name is required')    // Then check requirements
                ->minLength(2, 'Name must be at least 2 characters')
                ->maxLength(100, 'Name cannot exceed 100 characters'),

            'email' => Validator::isString()
                ->pipe('trim', 'strtolower')      // Clean and normalize
                ->nullifyEmpty()                  // Handle empty fields
                ->required('Email is required')   // Then check requirements
                ->email('Please enter a valid email address'),

            'subject' => Validator::isString()
                ->pipe('trim')                    // Clean whitespace
                ->nullifyEmpty()                  // Empty strings become null (form-safe)
                ->maxLength(200, 'Subject cannot exceed 200 characters')
                ->default('General Inquiry'),     // Use default for null values

            'message' => Validator::isString()
                ->pipe('trim')                    // Clean whitespace first
                ->nullifyEmpty()                  // Handle empty fields
                ->required('Message is required') // Then check requirements
                ->minLength(10, 'Message must be at least 10 characters')
                ->maxLength(2000, 'Message cannot exceed 2000 characters')
        ]);
    }

    public function validate(array $data): array
    {
        [$valid, $validatedData, $errors] = $this->validator->tryValidate($data);

        return [
            'valid' => $valid,
            'data' => $validatedData,
            'errors' => $errors
        ];
    }
}

// Usage in controller
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new ContactFormValidator();
    $result = $validator->validate($_POST);

    if ($result['valid']) {
        // Send email with validated data
        sendContactEmail($result['data']);
        $success = 'Thank you for your message!';
    } else {
        $errors = $result['errors'];
    }
}
?>

<!-- Display errors in template -->
<?php if (isset($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $field => $fieldErrors): ?>
            <?php foreach ($fieldErrors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (isset($success)): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>
```

## Form-Safe Validation with nullifyEmpty()

### The Problem with Empty Form Fields

HTML forms often submit empty strings for unfilled fields, which can create dangerous scenarios:

```php
// ❌ DANGEROUS: Without nullifyEmpty()
$orderValidator = Validator::isAssociative([
    'quantity' => Validator::isInt()->coerce(), // Empty string → 0 (dangerous!)
    'price' => Validator::isFloat()->coerce(),  // Empty string → 0.0 (dangerous!)
    'discount' => Validator::isFloat()->coerce()->default(5.0), // Empty → 0.0, not 5.0!
]);

// Form data with empty fields
$formData = [
    'quantity' => '', // Empty field from form
    'price' => '',    // Empty field from form
    'discount' => '', // Empty field from form
];

$result = $orderValidator->validate($formData);
// Result: ['quantity' => 0, 'price' => 0.0, 'discount' => 0.0]
// ❌ All dangerous zero values!
```

### The Solution: Form-Safe Validation

Use `nullifyEmpty()` to convert empty strings to `null`, then handle them appropriately:

```php
// ✅ SAFE: With nullifyEmpty()
$safeOrderValidator = Validator::isAssociative([
    'quantity' => Validator::isInt()
        ->coerce()
        ->nullifyEmpty() // Empty strings → null
        ->min(1, 'Quantity must be at least 1'), // Validation fails for null (safe!)

    'price' => Validator::isFloat()
        ->coerce()
        ->nullifyEmpty() // Empty strings → null
        ->positive('Price must be positive'), // Validation fails for null (safe!)

    'discount' => Validator::isFloat()
        ->coerce()
        ->nullifyEmpty() // Empty strings → null
        ->min(0.0)
        ->default(5.0), // Use default for null values (explicit!)
]);

$result = $safeOrderValidator->validate($formData);
// quantity and price validation will fail (safe!)
// discount will use the 5.0 default (explicit choice)
```

### Real-World Form Example

```php
<?php
class ProductFormValidator
{
    private $validator;

    public function __construct()
    {
        $this->validator = Validator::isAssociative([
            // Required fields
            'name' => Validator::isString()
                ->required('Product name is required')
                ->minLength(2, 'Name must be at least 2 characters'),

            'category' => Validator::isString()
                ->required('Category is required')
                ->oneOf(['electronics', 'clothing', 'books', 'home'], 'Invalid category'),

            // Optional fields with form-safe handling
            'description' => Validator::isString()
                ->nullifyEmpty() // Empty → null
                ->maxLength(1000, 'Description too long')
                ->default('No description provided'), // Explicit default

            'price' => Validator::isFloat()
                ->coerce()
                ->nullifyEmpty() // Empty → null (prevents $0.00 prices!)
                ->positive('Price must be greater than 0'),

            'weight' => Validator::isFloat()
                ->coerce()
                ->nullifyEmpty() // Empty → null
                ->positive('Weight must be positive'),

            'stock_quantity' => Validator::isInt()
                ->coerce()
                ->nullifyEmpty() // Empty → null
                ->min(0, 'Stock cannot be negative')
                ->default(0), // Explicit zero default for stock

            'discount_percentage' => Validator::isFloat()
                ->coerce()
                ->nullifyEmpty() // Empty → null
                ->min(0.0, 'Discount cannot be negative')
                ->max(100.0, 'Discount cannot exceed 100%')
                ->default(0.0), // No discount by default
        ]);
    }

    public function validate(array $formData): array
    {
        [$valid, $data, $errors] = $this->validator->tryValidate($formData);

        return [
            'valid' => $valid,
            'data' => $data,
            'errors' => $errors
        ];
    }
}

// Usage with form data containing empty fields
$formData = [
    'name' => 'Wireless Headphones',
    'category' => 'electronics',
    'description' => '', // Empty field
    'price' => '',       // Empty field - will fail validation (safe!)
    'weight' => '0.5',
    'stock_quantity' => '', // Empty field - will use default 0
    'discount_percentage' => '', // Empty field - will use default 0.0
];

$validator = new ProductFormValidator();
$result = $validator->validate($formData);

if (!$result['valid']) {
    // Price validation will fail because empty string → null → fails positive()
    // This prevents creating products with $0.00 price (safe!)
    echo "Validation errors: " . json_encode($result['errors']);
}
?>
```

### Key Benefits

1. **Prevents Dangerous Defaults**: Empty form fields don't become `0`, `0.0`, or `false`
2. **Explicit Control**: You choose what happens with empty fields via `default()`
3. **Database Consistency**: `NULL` values are often more appropriate than empty strings
4. **Business Logic Safety**: Distinguishes between "no value" and "zero value"

## User Registration Form

### Complete Registration Validator

```php
<?php
use Lemmon\Validator;

class UserRegistrationValidator
{
    public function createValidator(): \Lemmon\AssociativeValidator
    {
        return Validator::isAssociative([
            // Personal Information
            'first_name' => Validator::isString()
                ->required('First name is required')
                ->minLength(2, 'First name must be at least 2 characters')
                ->maxLength(50, 'First name cannot exceed 50 characters')
                ->pattern('/^[A-Za-z\s\'-]+$/', 'First name can only contain letters, spaces, hyphens, and apostrophes'),

            'last_name' => Validator::isString()
                ->required('Last name is required')
                ->minLength(2, 'Last name must be at least 2 characters')
                ->maxLength(50, 'Last name cannot exceed 50 characters')
                ->pattern('/^[A-Za-z\s\'-]+$/', 'Last name can only contain letters, spaces, hyphens, and apostrophes'),

            // Contact Information
            'email' => Validator::isString()
                ->required('Email is required')
                ->email('Please enter a valid email address')
                ->satisfies(
                    fn($email) => $this->isEmailUnique($email),
                    'This email address is already registered'
                ),

            'phone' => Validator::isString()
                ->pattern('/^\+?[1-9]\d{1,14}$/', 'Please enter a valid phone number'),

            // Account Information
            'username' => Validator::isString()
                ->required('Username is required')
                ->minLength(3, 'Username must be at least 3 characters')
                ->maxLength(30, 'Username cannot exceed 30 characters')
                ->pattern('/^[A-Za-z0-9_]+$/', 'Username can only contain letters, numbers, and underscores')
                ->satisfies(
                    fn($username) => $this->isUsernameUnique($username),
                    'This username is already taken'
                ),

            'password' => $this->createPasswordValidator(),

            'password_confirm' => Validator::isString()
                ->required('Please confirm your password')
                ->satisfies(
                    function ($value, $key, $input) {
                        return isset($input['password']) && $value === $input['password'];
                    },
                    'Password confirmation does not match'
                ),

            // Demographics
            'date_of_birth' => Validator::isString()
                ->required('Date of birth is required')
                ->date('Please enter a valid date (YYYY-MM-DD)')
                ->satisfies(
                    function ($date) {
                        $birthDate = new DateTime($date);
                        $today = new DateTime();
                        $age = $today->diff($birthDate)->y;
                        return $age >= 13;
                    },
                    'You must be at least 13 years old to register'
                ),

            'gender' => Validator::isString()
                ->oneOf(['male', 'female', 'other', 'prefer_not_to_say'], 'Please select a valid gender option')
                ->default('prefer_not_to_say'),

            // Address (optional)
            'address' => Validator::isAssociative([
                'street' => Validator::isString()->maxLength(100),
                'city' => Validator::isString()->maxLength(50),
                'state' => Validator::isString()->maxLength(50),
                'postal_code' => Validator::isString()
                    ->pattern('/^\d{5}(-\d{4})?$/', 'Please enter a valid postal code'),
                'country' => Validator::isString()->default('US')
            ]),

            // Preferences
            'newsletter' => Validator::isBool()->coerce()->default(false),
            'terms_accepted' => Validator::isBool()
                ->coerce()
                ->required('You must accept the terms and conditions')
                ->satisfies(
                    fn($value) => $value === true,
                    'You must accept the terms and conditions'
                )
        ]);
    }

    private function createPasswordValidator(): \Lemmon\StringValidator
    {
        return Validator::isString()
            ->required('Password is required')
            ->minLength(8, 'Password must be at least 8 characters')
            ->maxLength(128, 'Password cannot exceed 128 characters')
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
                fn($value) => preg_match('/[!@#$%^&*(),.?":{}|<>]/', $value),
                'Password must contain at least one special character'
            )
            ->satisfies(
                fn($value) => !preg_match('/(.)\1{2,}/', $value),
                'Password cannot contain more than 2 consecutive identical characters'
            );
    }

    private function isEmailUnique(string $email): bool
    {
        // Database check for email uniqueness
        // This would be implemented based on your database layer
        return !$this->userRepository->existsByEmail($email);
    }

    private function isUsernameUnique(string $username): bool
    {
        // Database check for username uniqueness
        return !$this->userRepository->existsByUsername($username);
    }
}
```

### Registration Form Handler

```php
<?php
class RegistrationController
{
    private $validator;
    private $userService;

    public function __construct(UserRegistrationValidator $validator, UserService $userService)
    {
        $this->validator = $validator;
        $this->userService = $userService;
    }

    public function handleRegistration(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }

        $schema = $this->validator->createValidator();
        [$valid, $validatedData, $errors] = $schema->tryValidate($_POST);

        if (!$valid) {
            return [
                'success' => false,
                'errors' => $errors,
                'message' => 'Please correct the errors below'
            ];
        }

        try {
            // Remove password confirmation before saving
            unset($validatedData['password_confirm']);

            // Hash password
            $validatedData['password'] = password_hash($validatedData['password'], PASSWORD_DEFAULT);

            // Create user
            $user = $this->userService->createUser($validatedData);

            // Send welcome email
            $this->sendWelcomeEmail($user);

            return [
                'success' => true,
                'message' => 'Registration successful! Please check your email to verify your account',
                'user_id' => $user->getId()
            ];

        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Registration failed. Please try again later'
            ];
        }
    }

    private function sendWelcomeEmail($user): void
    {
        // Implementation depends on your email service
    }
}
```

## E-commerce Product Form

### Product Creation/Update Form

```php
<?php
use Lemmon\Validator;

class ProductFormValidator
{
    public function createValidator(): \Lemmon\AssociativeValidator
    {
        return Validator::isAssociative([
            // Basic Information
            'name' => Validator::isString()
                ->required('Product name is required')
                ->minLength(3, 'Product name must be at least 3 characters')
                ->maxLength(200, 'Product name cannot exceed 200 characters'),

            'slug' => Validator::isString()
                ->required('Product slug is required')
                ->pattern('/^[a-z0-9-]+$/', 'Slug can only contain lowercase letters, numbers, and hyphens')
                ->satisfies(
                    fn($slug) => !str_starts_with($slug, '-') && !str_ends_with($slug, '-'),
                    'Slug cannot start or end with a hyphen'
                )
                ->satisfies(
                    fn($slug) => $this->isSlugUnique($slug),
                    'This slug is already in use'
                ),

            'description' => Validator::isString()
                ->required('Product description is required')
                ->minLength(50, 'Description must be at least 50 characters')
                ->maxLength(5000, 'Description cannot exceed 5000 characters'),

            'short_description' => Validator::isString()
                ->maxLength(500, 'Short description cannot exceed 500 characters'),

            // Pricing
            'price' => Validator::isFloat()
                ->coerce()
                ->required('Price is required')
                ->positive('Price must be positive')
                ->multipleOf(0.01, 'Price must be in cents (e.g., 19.99)'),

            'compare_price' => Validator::isFloat()
                ->coerce()
                ->positive('Compare price must be positive')
                ->multipleOf(0.01, 'Compare price must be in cents')
                ->satisfies(
                    function ($comparePrice, $key, $input) {
                        if (isset($input['price']) && $comparePrice) {
                            return $comparePrice > $input['price'];
                        }
                        return true;
                    },
                    'Compare price must be higher than the regular price'
                ),

            'cost_price' => Validator::isFloat()
                ->coerce()
                ->positive('Cost price must be positive')
                ->multipleOf(0.01, 'Cost price must be in cents'),

            // Inventory
            'sku' => Validator::isString()
                ->required('SKU is required')
                ->pattern('/^[A-Z0-9-]+$/', 'SKU can only contain uppercase letters, numbers, and hyphens')
                ->satisfies(
                    fn($sku) => $this->isSkuUnique($sku),
                    'This SKU is already in use'
                ),

            'track_inventory' => Validator::isBool()->coerce()->default(true),

            'inventory_quantity' => Validator::isInt()
                ->coerce()
                ->min(0, 'Inventory quantity cannot be negative')
                ->satisfies(
                    function ($quantity, $key, $input) {
                        $trackInventory = $input['track_inventory'] ?? true;
                        return !$trackInventory || $quantity !== null;
                    },
                    'Inventory quantity is required when tracking inventory'
                ),

            'allow_backorder' => Validator::isBool()->coerce()->default(false),

            // Categories and Tags
            'category_ids' => Validator::isArray(
                Validator::isInt()->positive()
            )
            ->required('At least one category is required')
            ->satisfies(
                fn($categories) => count($categories) > 0,
                'At least one category must be selected'
            )
            ->satisfies(
                fn($categories) => $this->validateCategoryIds($categories),
                'One or more selected categories do not exist'
            ),

            'tags' => Validator::isArray(
                Validator::isString()
                    ->minLength(2, 'Tags must be at least 2 characters')
                    ->maxLength(30, 'Tags cannot exceed 30 characters')
                    ->pattern('/^[a-z0-9-]+$/', 'Tags can only contain lowercase letters, numbers, and hyphens')
            ),

            // SEO
            'meta_title' => Validator::isString()
                ->maxLength(60, 'Meta title should not exceed 60 characters for SEO'),

            'meta_description' => Validator::isString()
                ->maxLength(160, 'Meta description should not exceed 160 characters for SEO'),

            // Status and Visibility
            'status' => Validator::isString()
                ->oneOf(['draft', 'active', 'archived'], 'Invalid product status')
                ->default('draft'),

            'published_at' => Validator::isString()
                ->datetime('Please enter a valid date and time')
                ->satisfies(
                    function ($datetime, $key, $input) {
                        if ($input['status'] === 'active' && !$datetime) {
                            return false;
                        }
                        return true;
                    },
                    'Published date is required for active products'
                ),

            // Shipping
            'weight' => Validator::isFloat()
                ->coerce()
                ->positive('Weight must be positive'),

            'dimensions' => Validator::isAssociative([
                'length' => Validator::isFloat()->coerce()->positive(),
                'width' => Validator::isFloat()->coerce()->positive(),
                'height' => Validator::isFloat()->coerce()->positive()
            ]),

            'requires_shipping' => Validator::isBool()->coerce()->default(true),

            // Digital Products
            'is_digital' => Validator::isBool()->coerce()->default(false),

            'download_files' => Validator::isArray(
                Validator::isAssociative([
                    'name' => Validator::isString()->required(),
                    'url' => Validator::isString()->url()->required(),
                    'size' => Validator::isInt()->positive()
                ])
            )
            ->satisfies(
                function ($files, $key, $input) {
                    $isDigital = $input['is_digital'] ?? false;
                    return !$isDigital || count($files) > 0;
                },
                'Digital products must have at least one download file'
            )
        ]);
    }

    private function isSlugUnique(string $slug, ?int $excludeId = null): bool
    {
        return !$this->productRepository->existsBySlug($slug, $excludeId);
    }

    private function isSkuUnique(string $sku, ?int $excludeId = null): bool
    {
        return !$this->productRepository->existsBySku($sku, $excludeId);
    }

    private function validateCategoryIds(array $categoryIds): bool
    {
        $existingIds = $this->categoryRepository->findExistingIds($categoryIds);
        return count($existingIds) === count($categoryIds);
    }
}
```

## Multi-Step Form Validation

### Step-by-Step Registration

```php
<?php
use Lemmon\Validator;

class MultiStepRegistrationValidator
{
    public function validateStep1(array $data): array
    {
        $validator = Validator::isAssociative([
            'email' => Validator::isString()
                ->required('Email is required')
                ->email('Please enter a valid email address'),

            'password' => Validator::isString()
                ->required('Password is required')
                ->minLength(8, 'Password must be at least 8 characters'),

            'password_confirm' => Validator::isString()
                ->required('Please confirm your password')
                ->satisfies(
                    function ($value, $key, $input) {
                        return isset($input['password']) && $value === $input['password'];
                    },
                    'Password confirmation does not match'
                )
        ]);

        return $this->validateStep($validator, $data);
    }

    public function validateStep2(array $data): array
    {
        $validator = Validator::isAssociative([
            'first_name' => Validator::isString()
                ->required('First name is required')
                ->minLength(2, 'First name must be at least 2 characters'),

            'last_name' => Validator::isString()
                ->required('Last name is required')
                ->minLength(2, 'Last name must be at least 2 characters'),

            'phone' => Validator::isString()
                ->required('Phone number is required')
                ->pattern('/^\+?[1-9]\d{1,14}$/', 'Please enter a valid phone number'),

            'date_of_birth' => Validator::isString()
                ->required('Date of birth is required')
                ->date('Please enter a valid date')
        ]);

        return $this->validateStep($validator, $data);
    }

    public function validateStep3(array $data): array
    {
        $validator = Validator::isAssociative([
            'street' => Validator::isString()
                ->required('Street address is required')
                ->minLength(5, 'Street address must be at least 5 characters'),

            'city' => Validator::isString()
                ->required('City is required')
                ->minLength(2, 'City must be at least 2 characters'),

            'state' => Validator::isString()
                ->required('State is required'),

            'postal_code' => Validator::isString()
                ->required('Postal code is required')
                ->pattern('/^\d{5}(-\d{4})?$/', 'Please enter a valid postal code'),

            'country' => Validator::isString()
                ->required('Country is required')
                ->default('US')
        ]);

        return $this->validateStep($validator, $data);
    }

    public function validateFinalStep(array $allData): array
    {
        // Combine all steps and validate the complete data
        $completeValidator = Validator::isAssociative([
            // Step 1
            'email' => Validator::isString()->required()->email(),
            'password' => Validator::isString()->required()->minLength(8),

            // Step 2
            'first_name' => Validator::isString()->required()->minLength(2),
            'last_name' => Validator::isString()->required()->minLength(2),
            'phone' => Validator::isString()->required()->pattern('/^\+?[1-9]\d{1,14}$/'),
            'date_of_birth' => Validator::isString()->required()->date(),

            // Step 3
            'street' => Validator::isString()->required()->minLength(5),
            'city' => Validator::isString()->required()->minLength(2),
            'state' => Validator::isString()->required(),
            'postal_code' => Validator::isString()->required()->pattern('/^\d{5}(-\d{4})?$/'),
            'country' => Validator::isString()->required(),

            // Final step
            'terms_accepted' => Validator::isBool()
                ->coerce()
                ->required('You must accept the terms')
                ->satisfies(fn($value) => $value === true, 'You must accept the terms'),

            'newsletter' => Validator::isBool()->coerce()->default(false)
        ]);

        return $this->validateStep($completeValidator, $allData);
    }

    private function validateStep($validator, array $data): array
    {
        [$valid, $validatedData, $errors] = $validator->tryValidate($data);

        return [
            'valid' => $valid,
            'data' => $validatedData,
            'errors' => $errors
        ];
    }
}

// Usage in controller
class MultiStepController
{
    private $validator;

    public function handleStep(int $step): array
    {
        $this->validator = new MultiStepRegistrationValidator();

        switch ($step) {
            case 1:
                return $this->validator->validateStep1($_POST);
            case 2:
                return $this->validator->validateStep2($_POST);
            case 3:
                return $this->validator->validateStep3($_POST);
            case 4:
                // Combine all session data and validate
                $allData = array_merge(
                    $_SESSION['step1_data'] ?? [],
                    $_SESSION['step2_data'] ?? [],
                    $_SESSION['step3_data'] ?? [],
                    $_POST
                );
                return $this->validator->validateFinalStep($allData);
            default:
                return ['valid' => false, 'errors' => ['Invalid step']];
        }
    }
}
```

## Best Practices for Form Validation

### 1. Sanitize Input Before Validation

```php
function sanitizeInput(array $data): array
{
    return array_map(function ($value) {
        if (is_string($value)) {
            return trim($value);
        }
        return $value;
    }, $data);
}

// Usage
$cleanData = sanitizeInput($_POST);
$result = $validator->validate($cleanData);
```

### 2. Provide User-Friendly Error Messages

```php
function formatErrorsForDisplay(array $errors): array
{
    $formatted = [];

    foreach ($errors as $field => $fieldErrors) {
        $fieldName = ucfirst(str_replace('_', ' ', $field));

        foreach ($fieldErrors as $error) {
            $formatted[] = "{$fieldName}: {$error}";
        }
    }

    return $formatted;
}
```

### 3. Handle File Uploads

```php
class FileUploadValidator
{
    public function validateProfilePicture(array $file): array
    {
        $validator = Validator::isAssociative([
            'name' => Validator::isString()
                ->required('File name is required')
                ->pattern('/\.(jpg|jpeg|png|gif)$/i', 'Only image files are allowed'),

            'size' => Validator::isInt()
                ->required('File size is required')
                ->max(5 * 1024 * 1024, 'File size cannot exceed 5MB'),

            'error' => Validator::isInt()
                ->required()
                ->satisfies(fn($error) => $error === UPLOAD_ERR_OK, 'File upload failed'),

            'type' => Validator::isString()
                ->required()
                ->oneOf(['image/jpeg', 'image/png', 'image/gif'], 'Invalid file type')
        ]);

        return $validator->tryValidate($file);
    }
}
```

## Next Steps

- 🔤 [String Validation Guide](../guides/string-validation.md) - Advanced string validation patterns
- 🔢 [Numeric Validation Guide](../guides/numeric-validation.md) - Numeric validation techniques
- 🏗️ [Object & Schema Validation](../guides/object-validation.md) - Complex nested structure validation
- ❌ [Error Handling Guide](../guides/error-handling.md) - Advanced error handling techniques
