# Error Handling Guide

The Lemmon Validator provides structured error handling with detailed feedback, schema-level aggregation, and flexible error reporting patterns.

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
    echo "Validation failed: " . implode(', ', array_map(fn($err) => $err->getMessage(), $e->getErrors()));
}
```

### Tuple-Based: `tryValidate()`

Returns a result tuple `[bool $valid, mixed $data, array $errors]`, where `$errors` is a flat
list of `ValidationError` objects (empty on success):

```php
$validator = Validator::isString()->email();

[$valid, $data, $errors] = $validator->tryValidate('invalid-email');

if ($valid) {
    echo "Valid email: " . $data;
} else {
    echo "Errors: " . implode(', ', array_map(fn($e) => $e->getMessage(), $errors));
    // Each $errors[$i] is a ValidationError with getPath(), getCode(), getMessage(), getParams()
}
```

## ValidationException Structure

`ValidationException::getErrors()` is the single access point for validation errors. It returns a
flat list of `ValidationError` objects, and an optional `$path` argument filters to one field:

```php
try {
    $validator->validate($invalidData);
} catch (ValidationException $e) {
    // Every error (a flat list of ValidationError objects)
    $all = $e->getErrors();

    // Just one field and everything nested beneath it
    $emailErrors = $e->getErrors('email');

    // Only root-level errors (those whose path is the empty string)
    $rootErrors = $e->getErrors('');

    // Exception message: a JSON dump of the errors (path, code, message, params)
    $message = $e->getMessage();
}
```

## Structured Errors

`getErrors()` returns a flat list of `ValidationError` value objects. Each one carries:

- `getCode()` — a stable machine-readable code (see `ValidationCode`), decoupled from wording
- `getMessage()` — the human-readable message
- `getPath()` — dotted location within the input (`''` at the root)
- `getParams()` — the values that produced the message (e.g. `['min' => 5]`)

```php
use Lemmon\Validator\ValidationCode;

[$valid, $data, $errors] = Validator::isString()->minLength(5)->tryValidate('hi');

$error = $errors[0];
$error->getCode();    // 'STRING_TOO_SHORT' (=== ValidationCode::STRING_TOO_SHORT)
$error->getMessage(); // 'Value must be at least 5 characters long'
$error->getPath();    // ''
$error->getParams();  // ['min' => 5]
```

Codes are stable across releases, so match on them rather than message text — this is what makes
i18n and programmatic handling reliable:

```php
foreach ($e->getErrors() as $error) {
    $label = match ($error->getCode()) {
        ValidationCode::REQUIRED => __('errors.required'),
        ValidationCode::STRING_TOO_SHORT => __('errors.too_short', $error->getParams()),
        ValidationCode::INVALID_TYPE => __('errors.invalid_type', $error->getParams()),
        default => $error->getMessage(),
    };
}
```

`ValidationError` implements `JsonSerializable`, so it serializes directly to
`{ "path": ..., "code": ..., "message": ..., "params": ... }` for API responses.

### Custom codes and message placeholders

`satisfies()` accepts an optional code and params. Params double as `{name}` placeholders in the
message:

```php
$validator = Validator::isString()->satisfies(
    fn($value) => preg_match_all('/\d/', $value) >= 2,
    'Must contain at least {min} digits',
    'PASSWORD_TOO_FEW_DIGITS',
    ['min' => 2],
);
// On failure: code 'PASSWORD_TOO_FEW_DIGITS', message 'Must contain at least 2 digits', params ['min' => 2]
```

## Error Code Reference

Codes are stable across releases and exposed as constants on `ValidationCode`. Match on the
constant (e.g. `ValidationCode::STRING_TOO_SHORT`), not the raw string or message text. Custom
`satisfies()` rules default to `CUSTOM` but may supply any code.

### Core

| Code           | Emitted by                          | Params                                                               |
| -------------- | ----------------------------------- | -------------------------------------------------------------------- |
| `REQUIRED`     | `required()` when the value is null | —                                                                    |
| `INVALID_TYPE` | any validator's type check          | `expected` (e.g. `'string'`, `'int'`, `'indexed_array'`, `'object'`) |
| `IN`           | `in()` / `oneOf()`                  | `allowed` (array)                                                    |
| `CONST`        | `const()`                           | `expected`                                                           |
| `ENUM`         | `enum()`                            | `allowed` (array of backed values or case names)                     |
| `CUSTOM`       | `satisfies()` (default)             | as supplied                                                          |

### Logical combinators

| Code      | Emitted by                              | Params |
| --------- | --------------------------------------- | ------ |
| `ALL_OF`  | `satisfiesAll()` / `Validator::allOf()` | —      |
| `ANY_OF`  | `satisfiesAny()` / `Validator::anyOf()` | —      |
| `NONE_OF` | `satisfiesNone()` / `Validator::not()`  | —      |

Each combinator emits a single error with its own code at the value's path; the inner validators' structured errors (their codes, paths, and params) are **not** surfaced. This is deliberate — for `ANY_OF` there is no single cause to report, and collapsing keeps the error shape predictable.

If you need the individual coded errors that a combinator flattens, chain the rules directly on the validator instead:

```php
// One ALL_OF error on failure:
Validator::isString()->satisfiesAll([
    Validator::isString()->minLength(8),
    Validator::isString()->pattern('/[A-Z]/'),
]);

// A single granular error per rule (STRING_TOO_SHORT, then PATTERN), fail-fast:
Validator::isString()->minLength(8)->pattern('/[A-Z]/');
```

### String

| Code               | Emitted by              | Params       |
| ------------------ | ----------------------- | ------------ |
| `STRING_TOO_SHORT` | `minLength()`           | `min`        |
| `STRING_TOO_LONG`  | `maxLength()`           | `max`        |
| `STRING_LENGTH`    | `length()`              | `length`     |
| `STRING_BETWEEN`   | `between()`             | `min`, `max` |
| `NOT_EMPTY`        | `notEmpty()`            | —            |
| `EMAIL`            | `email()`               | —            |
| `URL`              | `url()`                 | —            |
| `UUID`             | `uuid()`                | `variant`    |
| `IP`               | `ip()`                  | `version`    |
| `PATTERN`          | `pattern()` / `regex()` | `pattern`    |
| `DATETIME`         | `datetime()`            | `format`     |
| `DATE`             | `date()`                | `format`     |
| `HOSTNAME`         | `hostname()`            | —            |
| `DOMAIN`           | `domain()`              | —            |
| `TIME`             | `time()`                | —            |
| `BASE64`           | `base64()`              | `variant`    |
| `HEX`              | `hex()`                 | —            |

### Numeric

| Code               | Emitted by                             | Params       |
| ------------------ | -------------------------------------- | ------------ |
| `NUMBER_TOO_SMALL` | `min()`, `gte()` (and `nonNegative()`) | `min`        |
| `NUMBER_TOO_LARGE` | `max()`, `lte()` (and `nonPositive()`) | `max`        |
| `NUMBER_BETWEEN`   | `between()`                            | `min`, `max` |
| `GREATER_THAN`     | `gt()`                                 | `threshold`  |
| `LESS_THAN`        | `lt()`                                 | `threshold`  |
| `MULTIPLE_OF`      | `multipleOf()`                         | `divisor`    |
| `POSITIVE`         | `positive()`                           | —            |
| `NEGATIVE`         | `negative()`                           | —            |
| `PORT`             | `port()` (int)                         | —            |

### Array

| Code                   | Emitted by      | Params                                           |
| ---------------------- | --------------- | ------------------------------------------------ |
| `ARRAY_TOO_FEW_ITEMS`  | `minItems()`    | `min`                                            |
| `ARRAY_TOO_MANY_ITEMS` | `maxItems()`    | `max`                                            |
| `CONTAINS`             | `contains()`    | `value` (scalar form only)                       |
| `NOT_UNIQUE`           | `uniqueField()` | `field`, `value`, `others` (conflicting indices) |

## Errors for a Single Field

Pass a path to `getErrors()` to filter to one field and everything nested beneath it. This replaces
the need to walk a nested structure by hand:

```php
try {
    $schema->validate($input);
} catch (ValidationException $e) {
    $emailErrors   = $e->getErrors('email');           // errors at 'email'
    $addressErrors = $e->getErrors('address');         // 'address' plus 'address.street', 'address.zip', ...
    $streetErrors  = $e->getErrors('address.street');  // just that leaf
    $rootErrors    = $e->getErrors('');                // only root-level errors (empty path)
}
```

Path matching rules:

- **No argument** returns every error.
- **A field path** matches that exact path _and_ its descendants — `getErrors('address')` includes `address.street`. The match is segment-aware, so `getErrors('name')` will **not** pick up a sibling like `name_full`.
- **`''`** returns only root-level errors (scalar validator failures and container type errors all use the empty-string path).
- Returns an empty list (never `null`) when nothing matches.

### Error Path Convention

- **Root-level errors**: the empty string `''` (scalar validator failures and container type errors)
- **Field paths**: dot notation for nested fields (e.g. `'user.profile.email'`)
- **Array items**: index notation (e.g. `'items.0'`, `'items.1'`)

## API Responses

`ValidationError` implements `JsonSerializable`, so the error list drops straight into a JSON
response — no transformation step needed:

```php
try {
    $validated = $schema->validate($input);
    return ['success' => true, 'data' => $validated];
} catch (ValidationException $e) {
    return [
        'success' => false,
        'errors' => $e->getErrors(),
    ];
}
```

JSON output example:

```json
{
  "success": false,
  "errors": [
    {
      "path": "name",
      "code": "REQUIRED",
      "message": "Value is required",
      "params": {}
    },
    {
      "path": "email",
      "code": "EMAIL",
      "message": "Value must be a valid email address",
      "params": {}
    },
    {
      "path": "user.profile.phone",
      "code": "PATTERN",
      "message": "Invalid phone format",
      "params": { "pattern": "/^\\d{10}$/" }
    }
  ]
}
```

The same works from a `tryValidate()` tuple — its third element is the same `ValidationError[]`, so
`json_encode($errors)` produces identical output.

Serialization is safe by construction: malformed UTF-8 param keys are normalized, and if a param
value cannot be JSON-encoded (a resource, `NAN`/`INF`, or an object whose `jsonSerialize()` throws),
it renders as `"(complex value)"` rather than making the response fail.

## Fail-Fast Per Field

Each validator chain stops at the first failing rule. Schema validation still aggregates errors across fields:

### Single Field Fail-Fast

```php
$validator = Validator::isString()
    ->required()
    ->minLength(8)
    ->email()
    ->pattern('/^[a-z]/', 'Email must start with lowercase letter');

[$valid, $data, $errors] = $validator->tryValidate('AB');

// $errors contains the first failure in the chain as a single ValidationError:
// $errors[0]->getMessage() === 'Value must be at least 8 characters long'
// $errors[0]->getCode()    === 'STRING_TOO_SHORT'
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

// $errors is a flat list of ValidationError objects, each with a dotted path:
// [
//     ValidationError(path: 'name',  code: 'STRING_TOO_SHORT', message: 'Value must be at least 2 characters long'),
//     ValidationError(path: 'email', code: 'EMAIL',            message: 'Value must be a valid email address'),
//     ValidationError(path: 'age',   code: 'NUMBER_TOO_SMALL', message: 'Value must be at least 18'),
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

// $errors is a flat list of ValidationError objects with full dotted paths:
// [
//     ValidationError(path: 'items.1', code: 'NUMBER_TOO_SMALL', message: 'Value must be at least 1'),
//     ValidationError(path: 'items.2', code: 'NUMBER_TOO_SMALL', message: 'Value must be at least 1'),
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
    $paths = array_map(fn($err) => $err->getPath(), $e->getErrors());
    // ['users.0.email', 'users.1.email']
}
```

**Cross-Item Validation Errors (Field-Level):**

For uniqueness of a nested field, use `uniqueField()` — it produces the nested error structure and field-level paths automatically:

```php
$schema = Validator::isAssociative([
    'symlinks' => Validator::isArray()
        ->items(Validator::isAssociative([
            'destination' => Validator::isString()->required(),
        ]))
        ->uniqueField('destination'),
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
    foreach ($e->getErrors() as $err) {
        echo "{$err->getPath()}: {$err->getMessage()}\n";
    }
    // symlinks.0.destination: Value '/path1' is not unique (also at index 2)
    // symlinks.2.destination: Value '/path1' is not unique (also at index 0)
}
```

For custom cross-item logic, use `satisfies()` and throw a `ValidationException` containing `ValidationError` objects whose `path` is `"{index}.{field}"` (e.g. `new ValidationError("2.destination", ValidationCode::NOT_UNIQUE, $message)`) to get field-level paths.

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
    // Display errors to user ($result['errors'] is a list of ValidationError objects)
    foreach ($result['errors'] as $error) {
        echo "<div class='error'>{$error->getPath()}: {$error->getMessage()}</div>";
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
                ->in(['success', 'error'], 'Status must be success or error'),

            'data' => Validator::isAssociative()
                ->required('Data is required'),

            'timestamp' => Validator::isString()
                ->required('Timestamp is required')
                ->datetime('Y-m-d\TH:i:sP', 'Timestamp must be valid ISO 8601 format')
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
                'driver' => Validator::isString()->in(['redis', 'memcached', 'file'])->default('file'),
                'ttl' => Validator::isInt()->positive()->default(3600)
            ])->default([]),

            'debug' => Validator::isBool()->default(false)
        ]);

        [$valid, $validatedConfig, $errors] = $schema->tryValidate($config);

        if (!$valid) {
            $errorMessage = "Configuration validation failed:\n";
            foreach ($errors as $error) {
                $errorMessage .= "- {$error->getPath()}: {$error->getMessage()}\n";
            }
            throw new InvalidConfigurationException($errorMessage);
        }

        return $validatedConfig;
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
class UserValidationException extends Exception
{
    private array $errors;

    public function __construct(array $errors, string $context = 'user validation')
    {
        $this->errors = $errors;

        $messages = array_map(fn($error) => $error->getMessage(), $errors);
        $message = "User validation failed in {$context}: " . implode(', ', $messages);

        parent::__construct($message);
    }

    public function getErrors(): array
    {
        return $this->errors;
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
// Use validate() when you want exceptions
try {
    $email = Validator::isString()->email()->validate($input);
    sendEmail($email);
} catch (ValidationException $e) {
    logError($e->getMessage());
}

// Use tryValidate() when you need to handle errors without exceptions
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
use Lemmon\Validator\ValidationError;

function displayErrors(array $errors): void
{
    // $errors is a flat list of ValidationError objects with dotted paths -- no recursion needed.
    // Root-level errors have an empty path; label them for display at the edge as you see fit.
    foreach ($errors as $error) {
        $field = $error->getPath() === '' ? '(root)' : $error->getPath();
        echo "<div class='error'>{$field}: {$error->getMessage()}</div>";
    }
}
```

## Next Steps

- [Custom Validation Guide](custom-validation.md) — Complex validation scenarios
- [Form Validation Examples](../examples/form-validation.md) — See error handling in action
- [API Reference - Validator Factory](../api-reference/validator-factory.md) — Complete method reference
