# Lemmon Validator

[![CI](https://github.com/lemmon/validator-php/actions/workflows/ci.yml/badge.svg)](https://github.com/lemmon/validator-php/actions/workflows/ci.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/lemmon/validator.svg)](https://packagist.org/packages/lemmon/validator)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A lightweight, fluent validation library for PHP, inspired by Valibot and Zod.

## Installation

Install the package with Composer:

```bash
composer require lemmon/validator
```

## Usage

### Associative array validation

```php
use Lemmon\Validator;

// Define a schema for an associative array. Fields are optional by default.
$schema = Validator::isAssociative([
  'required' => Validator::isString()->required(),
  'optional' => Validator::isString(),
  'forced'   => Validator::isString()->default('Hello!'),
  'level'    => Validator::isInt()->coerce()->oneOf([3, 5, 8])->default(3),
  'override' => Validator::isBool()->coerce()->default(false),
])->required(); // Make the top-level schema required to ensure it's an array

$input = [
    'required' => 'test',
    'level' => '5',
];

// throws ValidationException on error
$data = $schema->validate($input);

// or use tryValidate for non-throwing validation
[$valid, $data, $errors] = $schema->tryValidate($input);

// You can also define an empty schema
$emptySchema = Validator::isAssociative();
$emptyData = $emptySchema->validate([]); // Validates an empty array

// Unlike the main example, if the SchemaValidator is not explicitly required(), it will also pass with a null value.
[$validNull, $dataNull, $errorsNull] = $emptySchema->tryValidate(null);
// $validNull will be true, $dataNull will be null, $errorsNull will be null
```

### Object validation

Similar to associative arrays, you can validate `stdClass` objects.

```php
use Lemmon\Validator;

// Define a schema for an object
$schema = Validator::isObject([
  'name' => Validator::isString()->required(),
  'age'  => Validator::isInt()->coerce(),
]);

// Validate a stdClass object
$object = (object)['name' => 'John Doe', 'age' => '42'];
$validatedObject = $schema->validate($object); // ✓ $validatedObject->age is now (int) 42

// Coerce an associative array into a stdClass object
$array = ['name' => 'Jane Doe', 'age' => 30];
$coercedObject = $schema->coerce()->validate($array); // ✓
```

### Array validation

```php
use Lemmon\Validator;

// Validate plain arrays
$arrayValidator = Validator::isArray();
$data = $arrayValidator->validate([1, 2, 3, 'foo']); // ✓

// With type validation for items
$stringArrayValidator = Validator::isArray()->items(Validator::isString());
$data = $stringArrayValidator->validate(['foo', 'bar', 'baz']); // ✓

// With coercion
$coercedArrayValidator = Validator::isArray()->coerce();
$data = $coercedArrayValidator->validate(['key' => 'value', 'foo' => 'bar']); // becomes ['value', 'bar']
$data = $coercedArrayValidator->validate('single'); // becomes ['single']
$data = $coercedArrayValidator->validate(123); // becomes [123]
$data = $coercedArrayValidator->validate(''); // becomes [] (empty string coerces to empty array)
```

### Numeric validation

```php
use Lemmon\Validator;

// Integer validation - returns int values
$intValidator = Validator::isInt();
$data = $intValidator->validate(42); // 42 (int)

// With constraints
$constrainedInt = Validator::isInt()->min(1)->max(100)->positive();
$data = $constrainedInt->validate(50); // 50

// With coercion
$coercedInt = Validator::isInt()->coerce();
$data = $coercedInt->validate('42'); // 42 (int)

// Multiple validation
$evenNumbers = Validator::isInt()->multipleOf(2);
$data = $evenNumbers->validate(8); // 8

// Float validation - returns float values
$floatValidator = Validator::isFloat();
$data = $floatValidator->validate(42.5); // 42.5 (float)

// With constraints
$constrainedFloat = Validator::isFloat()->min(0.1)->max(99.9)->multipleOf(0.5);
$data = $constrainedFloat->validate(10.5); // 10.5

// Negative numbers
$negativeFloat = Validator::isFloat()->negative();
$data = $negativeFloat->validate(-3.14); // -3.14
```

### String format validation

```php
use Lemmon\Validator;

// Email validation
$emailValidator = Validator::isString()->email();
$data = $emailValidator->validate('user@example.com'); // ✓

// URL validation
$urlValidator = Validator::isString()->url();
$data = $urlValidator->validate('https://example.com'); // ✓

// UUID validation
$uuidValidator = Validator::isString()->uuid();
$data = $uuidValidator->validate('550e8400-e29b-41d4-a716-446655440000'); // ✓

// IP address validation
$ipValidator = Validator::isString()->ip();
$data = $ipValidator->validate('192.168.1.1'); // ✓
$data = $ipValidator->validate('2001:0db8:85a3::8a2e:0370:7334'); // ✓

// String length validation
$usernameValidator = Validator::isString()->minLength(3)->maxLength(20);
$data = $usernameValidator->validate('john_doe'); // ✓

// Pattern matching
$phoneValidator = Validator::isString()->pattern('/^\d{3}-\d{3}-\d{4}$/');
$data = $phoneValidator->validate('123-456-7890'); // ✓

// Date and datetime validation
$dateValidator = Validator::isString()->date(); // Y-m-d format
$data = $dateValidator->validate('2023-12-25'); // ✓

$datetimeValidator = Validator::isString()->datetime(); // ISO 8601 format
$data = $datetimeValidator->validate('2023-12-25T10:30:00'); // ✓

// Custom formats with custom error messages
$strongPassword = Validator::isString()
    ->minLength(8, 'Password must be at least 8 characters')
    ->pattern('/[A-Z]/', 'Password must contain uppercase letter')
    ->pattern('/[0-9]/', 'Password must contain a number');
```

### Advanced validation with logical combinators

```php
use Lemmon\Validator;

// All conditions must pass
$strictValidator = Validator::isString()->allOf([
    Validator::isString()->minLength(5),
    Validator::isString()->maxLength(20),
    Validator::isString()->pattern('/^[a-zA-Z0-9]+$/')
]);

// At least one condition must pass
$flexibleValidator = Validator::isString()->anyOf([
    Validator::isString()->email(),
    Validator::isString()->url(),
    Validator::isString()->uuid()
]);

// Value must NOT match the condition
$nonEmailValidator = Validator::isString()->not(
    Validator::isString()->email()
);

// Complex combinations
$advancedValidator = Validator::isString()
    ->minLength(1)
    ->anyOf([
        Validator::isString()->email(),
        Validator::isString()->allOf([
            Validator::isString()->minLength(10),
            Validator::isString()->pattern('/^[A-Z]/')
        ])
    ]);
```

### Comprehensive error handling

```php
use Lemmon\Validator;

// Get all validation errors at once
$validator = Validator::isString()
    ->minLength(10)
    ->maxLength(5)  // Impossible condition
    ->email();

[$valid, $data, $errors] = $validator->tryValidate('short');

if (!$valid) {
    print_r($errors);
    // Output:
    // [
    //     'Value must be at least 10 characters long.',
    //     'Value must be at most 5 characters long.',
    //     'Value must be a valid email address.'
    // ]
}
```

### Custom validation with context

```php
use Lemmon\Validator;

// Context-aware custom validation
$contextValidator = Validator::isString()->addValidation(
    function ($value, $key, $input) {
        // Access the field key and full input for complex validation
        if ($key === 'password_confirm' && isset($input['password'])) {
            return $value === $input['password'];
        }
        return true;
    },
    'Password confirmation does not match.'
);

$schema = Validator::isAssociative([
    'password' => Validator::isString()->minLength(8),
    'password_confirm' => $contextValidator
]);
```

### Field Validator (Standalone)

```php
use Lemmon\Validator;

// Validate a single string value
$stringValidator = Validator::isString();
$data = $stringValidator->validate('hello'); // 'hello'

// Use tryValidate for non-throwing validation
[$valid, $data, $errors] = $stringValidator->tryValidate(123);
// $valid will be false, $data will be 123, $errors will be ['Value must be a string.']

// Optional field handling
$optionalString = Validator::isString();
$data = $optionalString->validate(null); // null (passes, as not required)

$requiredString = Validator::isString()->required();
// $requiredString->validate(null); // Throws ValidationException: ['Value is required.']

// Nullify empty values
$nullifyingString = Validator::isString()->nullifyEmpty();
$data = $nullifyingString->validate(''); // null

$nullifyingArray = Validator::isArray()->nullifyEmpty();
$data = $nullifyingArray->validate([]); // null
```
