# Lemmon Validator

[![CI](https://github.com/lemmon/validator-php/actions/workflows/ci.yml/badge.svg)](https://github.com/lemmon/validator-php/actions/workflows/ci.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A lightweight, fluent validation library for PHP, inspired by Valibot and Zod.

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
