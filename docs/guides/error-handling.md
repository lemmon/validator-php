# Error Handling Guide

The Lemmon Validator provides comprehensive error handling with detailed feedback, structured error collection, and flexible error reporting patterns.

## Validation Methods

The library provides two validation approaches with different error handling strategies:

### Exception-Based: `validate()`

Throws a `ValidationException` when validation fails:

```php
use Lemmon\Validator\Validator;
use Lemmon\Validator\ValidationException;

$validator = Validator::isString()->email();

try {
    $result = $validator->validate('invalid-email');
    echo "Valid: " . $result;
} catch (ValidationException $e) {
    echo "Validation failed: " . implode(', ', $e->getErrors());
}
```

### Tuple-Based: `tryValidate()`

Returns a result tuple `[bool $valid, mixed $data, array $errors]`:

```php
$validator = Validator::isString()->email();

[$valid, $data, $errors] = $validator->tryValidate('invalid-email');

if ($valid) {
    echo "Valid email: " . $data;
} else {
    echo "Errors: " . implode(', ', $errors);
    // Handle errors without exception handling
}
```

## ValidationException Structure

The `ValidationException` class provides structured access to validation errors:

```php
try {
    $validator->validate($invalidData);
} catch (ValidationException $e) {
    // Get all error messages as array
    $errors = $e->getErrors();

    // Get exception message (first error or summary)
    $message = $e->getMessage();

    // Standard exception properties
    $code = $e->getCode();
    $file = $e->getFile();
    $line = $e->getLine();
}
```

## Flattened Errors for API Consumption

For API responses, you often need a flat list of errors with field paths. The `ValidationException` class provides methods to convert nested error structures into a flattened format suitable for frontend consumption.

### Using getFlattenedErrors() with Exceptions

When catching a `ValidationException`, use `getFlattenedErrors()` to get a flattened list:

```php
use Lemmon\Validator\ValidationException;

try {
    $schema->validate($input);
} catch (ValidationException $e) {
    $flattened = $e->getFlattenedErrors();
    // Returns: [
    //     ['path' => 'name', 'message' => 'Value is required'],
    //     ['path' => 'email', 'message' => 'Value must be a valid email address'],
    //     ['path' => 'user.profile.phone', 'message' => 'Invalid phone format']
    // ]
}
```

### Using flattenErrors() with tryValidate()

When using `tryValidate()` (which doesn't throw exceptions), use the static `flattenErrors()` method:

```php
use Lemmon\Validator\ValidationException;

[$valid, $data, $errors] = $validator->tryValidate($input);

if (!$valid) {
    $flattened = ValidationException::flattenErrors($errors);
    // Returns empty array if $errors is null
    // Otherwise returns same format as getFlattenedErrors()
}
```

### Error Path Convention

- **Root-level errors**: Use `'_root'` path for scalar validator errors and container type errors
- **Field paths**: Use dot notation for nested fields (e.g., `'user.profile.email'`)
- **Array items**: Use index notation (e.g., `'items.0'`, `'items.1'`)

### Example: API Response Format

```php
try {
    $schema->validate($input);
    return ['success' => true, 'data' => $validated];
} catch (ValidationException $e) {
    return [
        'success' => false,
        'errors' => $e->getFlattenedErrors()
    ];
}
```

JSON output example:
```json
{
  "success": false,
  "errors": [
    {"path": "name", "message": "Value is required"},
    {"path": "email", "message": "Value must be a valid email address"},
    {"path": "user.profile.phone", "message": "Invalid phone format"}
  ]
}
```

### Example: Root-Level Errors

For scalar validators or container type errors:

```php
try {
    Validator::isString()->email()->validate('invalid');
} catch (ValidationException $e) {
    $flattened = $e->getFlattenedErrors();
    // Returns: [
    //     ['path' => '_root', 'message' => 'Value must be a valid email address']
    // ]
}
```

## Comprehensive Error Collection

Unlike many validators that stop at the first error, Lemmon Validator collects **all** validation errors:

### Single Field Multiple Errors

```php
$validator = Validator::isString()
    ->required()
    ->minLength(8)
    ->email()
    ->pattern('/^[a-z]/', 'Email must start with lowercase letter');

[$valid, $data, $errors] = $validator->tryValidate('AB');

// $errors contains ALL failures:
// [
//     'Value must be at least 8 characters long',
//     'Value must be a valid email address',
//     'Email must start with lowercase letter'
// ]
```

### Schema Validation Errors

For nested structures, errors are collected hierarchically:

```php
$userSchema = Validator::isAssociative([
    'name' => Validator::isString()->required()->minLength(2),
    'email' => Validator::isString()->required()->email(),
    'age' => Validator::isInt()->min(18)->max(120)
]);

$invalidData = [
    'name' => 'A',              // Too short
    'email' => 'invalid-email', // Invalid format
    'age' => 15                 // Too young
];

[$valid, $data, $errors] = $userSchema->tryValidate($invalidData);

// $errors structure:
// [
//     'name' => ['Value must be at least 2 characters long'],
//     'email' => ['Value must be a valid email address'],
//     'age' => ['Value must be at least 18']
// ]
```

### Array Item Validation Errors

For arrays with item validators, errors preserve array indices to identify which item failed:

**Standard Item Errors:**
Errors from item validation automatically preserve indices:

```php
$schema = Validator::isAssociative([
    'items' => Validator::isArray()->items(Validator::isInt()->min(1)),
]);

$input = [
    'items' => [5, -2, 0, 10], // Items at index 1 and 2 are invalid
];

[$valid, $data, $errors] = $schema->tryValidate($input);

// $errors structure preserves array indices:
// [
//     'items' => [
//         '1' => ['Value must be at least 1'],
//         '2' => ['Value must be at least 1']
//     ]
// ]

// Flattened errors show full paths with indices:
$flattened = ValidationException::flattenErrors($errors);
// [
//     ['path' => 'items.1', 'message' => 'Value must be at least 1'],
//     ['path' => 'items.2', 'message' => 'Value must be at least 1']
// ]
```

For nested structures with array items, the full path including indices is preserved:

```php
$schema = Validator::isAssociative([
    'users' => Validator::isArray()->items(Validator::isAssociative([
        'name' => Validator::isString()->required(),
        'email' => Validator::isString()->email()->required(),
    ])),
]);

$input = [
    'users' => [
        ['name' => 'John'], // Missing email at index 0
        ['name' => 'Jane', 'email' => 'invalid'], // Invalid email at index 1
    ],
];

try {
    $schema->validate($input);
} catch (ValidationException $e) {
    $flattened = $e->getFlattenedErrors();
    // [
    //     ['path' => 'users.0.email', 'message' => 'Value is required'],
    //     ['path' => 'users.1.email', 'message' => 'Value must be a valid email address']
    //     ]
}
```

**Cross-Item Validation Errors (Field-Level):**

For cross-item validations (like uniqueness) that need to attach errors to specific fields within items, use nested error structure:

```php
$schema = Validator::isAssociative([
    'symlinks' => Validator::isArray()
        ->items(Validator::isAssociative([
            'destination' => Validator::isString()->required(),
        ]))
        ->satisfies(
            function ($symlinks) {
                // Check uniqueness
                $destinations = [];
                foreach ($symlinks as $index => $item) {
                    $dest = $item['destination'] ?? null;
                    if ($dest) {
                        $destinations[$dest][] = $index;
                    }
                }

                $duplicates = [];
                foreach ($destinations as $dest => $indices) {
                    if (count($indices) > 1) {
                        $duplicates[$dest] = $indices;
                    }
                }

                if (empty($duplicates)) return true;

                // Nested structure: [index => [field => [message]]]
                // Flattens to 'symlinks.2.destination'
                $errors = [];
                foreach ($duplicates as $dest => $indices) {
                    foreach (array_slice($indices, 1) as $idx) {
                        $errors[$idx] = [
                            'destination' => ["'{$dest}' is not unique"]
                        ];
                    }
                }
                throw new ValidationException($errors);
            }
        )
]);

try {
    $schema->validate([
        'symlinks' => [
            ['destination' => '/path1'],
            ['destination' => '/path2'],
            ['destination' => '/path1'], // Duplicate
        ],
    ]);
} catch (ValidationException $e) {
    $flattened = $e->getFlattenedErrors();
    // [
    //     ['path' => 'symlinks.2.destination', 'message' => "'/path1' is not unique"]
    // ]
}
```

**Key Pattern:** Structure errors as `[arrayIndex => [fieldName => [errorMessage]]]` to get field-level error paths in flattened output.

## Error Message Customization

### Built-in Validator Messages

Most built-in validators accept custom error messages:

```php
$customValidator = Validator::isString()
    ->required('Name is required')
    ->minLength(2, 'Name must be at least 2 characters')
    ->email('Please enter a valid email address');
```

### Custom Validation Messages

For `satisfies()`, provide custom messages:

```php
$strongPasswordValidator = Validator::isString()
    ->satisfies(
        fn($value) => preg_match('/[A-Z]/', $value),
        'Password must contain at least one uppercase letter'
    )
    ->satisfies(
        fn($value) => preg_match('/\d/', $value),
        'Password must contain at least one number'
    );
```

## Error Handling Patterns

### Web Form Validation

```php
class FormValidator
{
    public function validateRegistration(array $data): array
    {
        $schema = Validator::isAssociative([
            'username' => Validator::isString()
                ->required('Username is required')
                ->minLength(3, 'Username must be at least 3 characters')
                ->pattern('/^[a-zA-Z0-9_]+$/', 'Username can only contain letters, numbers, and underscores'),

            'email' => Validator::isString()
                ->required('Email is required')
                ->email('Please enter a valid email address'),

            'password' => Validator::isString()
                ->required('Password is required')
                ->minLength(8, 'Password must be at least 8 characters'),

            'age' => Validator::isInt()
                ->coerce()
                ->required('Age is required')
                ->min(13, 'You must be at least 13 years old')
        ]);

        [$valid, $validatedData, $errors] = $schema->tryValidate($data);

        return [
            'valid' => $valid,
            'data' => $validatedData,
            'errors' => $errors
        ];
    }
}

// Usage
$validator = new FormValidator();
$result = $validator->validateRegistration($_POST);

if ($result['valid']) {
    // Process valid data
    $user = createUser($result['data']);
} else {
    // Display errors to user
    foreach ($result['errors'] as $field => $fieldErrors) {
        foreach ($fieldErrors as $error) {
            echo "<div class='error'>{$field}: {$error}</div>";
        }
    }
}
```

### API Response Validation

```php
class ApiValidator
{
    public function validateApiResponse(array $response): void
    {
        $schema = Validator::isAssociative([
            'status' => Validator::isString()
                ->required('Status is required')
                ->oneOf(['success', 'error'], 'Status must be success or error'),

            'data' => Validator::isAssociative()
                ->required('Data is required'),

            'timestamp' => Validator::isString()
                ->required('Timestamp is required')
                ->datetime('Timestamp must be valid ISO 8601 format')
        ]);

        try {
            $validatedResponse = $schema->validate($response);
            // Process valid response
        } catch (ValidationException $e) {
            // Log validation errors
            error_log('API Response Validation Failed: ' . json_encode($e->getErrors()));

            // Throw custom exception
            throw new InvalidApiResponseException(
                'Invalid API response format',
                previous: $e
            );
        }
    }
}
```

### Configuration Validation

```php
class ConfigValidator
{
    public function validateConfig(array $config): array
    {
        $schema = Validator::isAssociative([
            'database' => Validator::isAssociative([
                'host' => Validator::isString()->required('Database host is required'),
                'port' => Validator::isInt()->min(1)->max(65535)->default(3306),
                'username' => Validator::isString()->required('Database username is required'),
                'password' => Validator::isString()->required('Database password is required'),
                'database' => Validator::isString()->required('Database name is required')
            ])->required('Database configuration is required'),

            'cache' => Validator::isAssociative([
                'driver' => Validator::isString()->oneOf(['redis', 'memcached', 'file'])->default('file'),
                'ttl' => Validator::isInt()->positive()->default(3600)
            ])->default([]),

            'debug' => Validator::isBool()->default(false)
        ]);

        [$valid, $validatedConfig, $errors] = $schema->tryValidate($config);

        if (!$valid) {
            $errorMessage = "Configuration validation failed:\n";
            $this->flattenErrors($errors, $errorMessage);
            throw new InvalidConfigurationException($errorMessage);
        }

        return $validatedConfig;
    }

    private function flattenErrors(array $errors, string &$message, string $prefix = ''): void
    {
        foreach ($errors as $key => $value) {
            $currentKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value) && !empty($value) && is_string($value[0])) {
                // Leaf error messages
                foreach ($value as $error) {
                    $message .= "- {$currentKey}: {$error}\n";
                }
            } elseif (is_array($value)) {
                // Nested errors
                $this->flattenErrors($value, $message, $currentKey);
            }
        }
    }
}
```

## Error Context and Debugging

### Adding Context to Errors

```php
$contextValidator = Validator::isString()->satisfies(
    function ($value, $key, $input) {
        if ($key === 'email' && isset($input['domain_whitelist'])) {
            $domain = substr(strrchr($value, '@'), 1);
            return in_array($domain, $input['domain_whitelist']);
        }
        return true;
    },
    'Email domain is not in the allowed list'
);
```

### Debugging Validation Issues

```php
class ValidationDebugger
{
    public static function debugValidation($validator, $data): void
    {
        [$valid, $result, $errors] = $validator->tryValidate($data);

        echo "=== Validation Debug ===\n";
        echo "Input: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        echo "Valid: " . ($valid ? 'true' : 'false') . "\n";
        echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
        echo "Errors: " . json_encode($errors, JSON_PRETTY_PRINT) . "\n";
        echo "========================\n";
    }
}

// Usage
$validator = Validator::isAssociative([
    'name' => Validator::isString()->required()->minLength(2),
    'email' => Validator::isString()->email()
]);

ValidationDebugger::debugValidation($validator, ['name' => 'A', 'email' => 'invalid']);
```

## Advanced Error Handling

### Custom Exception Types

```php
class UserValidationException extends ValidationException
{
    public function __construct(array $errors, string $context = 'user validation')
    {
        $message = "User validation failed in {$context}: " . implode(', ', $this->flattenErrors($errors));
        parent::__construct($errors, $message);
    }

    private function flattenErrors(array $errors): array
    {
        $flattened = [];
        array_walk_recursive($errors, function($error) use (&$flattened) {
            if (is_string($error)) {
                $flattened[] = $error;
            }
        });
        return $flattened;
    }
}
```

### Error Aggregation

```php
class ValidationAggregator
{
    private array $validators = [];
    private array $contexts = [];

    public function add(string $context, $validator, $data): self
    {
        $this->validators[] = $validator;
        $this->contexts[] = ['context' => $context, 'data' => $data];
        return $this;
    }

    public function validateAll(): array
    {
        $allErrors = [];
        $allData = [];
        $overallValid = true;

        foreach ($this->validators as $index => $validator) {
            $context = $this->contexts[$index]['context'];
            $data = $this->contexts[$index]['data'];

            [$valid, $validatedData, $errors] = $validator->tryValidate($data);

            if (!$valid) {
                $allErrors[$context] = $errors;
                $overallValid = false;
            } else {
                $allData[$context] = $validatedData;
            }
        }

        return [$overallValid, $allData, $allErrors];
    }
}

// Usage
$aggregator = new ValidationAggregator();
$aggregator
    ->add('user', $userValidator, $userData)
    ->add('profile', $profileValidator, $profileData)
    ->add('settings', $settingsValidator, $settingsData);

[$allValid, $allData, $allErrors] = $aggregator->validateAll();
```

## Best Practices

### 1. Use Appropriate Validation Method

```php
// Use validate() when you want to fail fast
try {
    $email = Validator::isString()->email()->validate($input);
    sendEmail($email);
} catch (ValidationException $e) {
    logError($e->getMessage());
}

// Use tryValidate() when you need to handle errors gracefully
[$valid, $data, $errors] = $validator->tryValidate($input);
if ($valid) {
    processData($data);
} else {
    showUserFriendlyErrors($errors);
}
```

### 2. Provide Meaningful Error Messages

```php
// Good: Specific and actionable
$validator = Validator::isString()
    ->minLength(8, 'Password must be at least 8 characters long')
    ->pattern('/[A-Z]/', 'Password must contain at least one uppercase letter');

// Avoid: Generic and unhelpful
$validator = Validator::isString()
    ->minLength(8, 'Invalid')
    ->pattern('/[A-Z]/', 'Error');
```

### 3. Handle Nested Errors Appropriately

```php
function displayErrors(array $errors, string $prefix = ''): void
{
    foreach ($errors as $key => $value) {
        $fieldName = $prefix ? "{$prefix}.{$key}" : $key;

        if (is_array($value) && isset($value[0]) && is_string($value[0])) {
            // Field errors
            foreach ($value as $error) {
                echo "<div class='error'>{$fieldName}: {$error}</div>";
            }
        } elseif (is_array($value)) {
            // Nested structure errors
            displayErrors($value, $fieldName);
        }
    }
}
```

## Next Steps

- [Custom Validation Guide](custom-validation.md) -- Complex validation scenarios
- [Form Validation Examples](../examples/form-validation.md) -- See error handling in action
- [API Reference - Validator Factory](../api-reference/validator-factory.md) -- Complete method reference
