# Lemmon Validator

[![CI](https://github.com/lemmon/validator-php/actions/workflows/ci.yml/badge.svg)](https://github.com/lemmon/validator-php/actions/workflows/ci.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A lightweight, fluent validation library for PHP, inspired by Valibot and Zod.

## Usage

```php
use Lemmon\Validator;

$schema = Validator::isArray([
  'required' => Validator::isString()->required(),
  'optional' => Validator::isString(),
  'forced'   => Validator::isString()->default('Hello!'),
  'level'    => Validator::isInt()->coerce()->oneOf([3, 5, 8])->default(3),
  'override' => Validator::isBool()->coerce()->default(false),
])->coerceAll();

$input = [
    'required' => 'test',
    'level' => '5',
];

// throws ValidationException on error
$data = $schema->validate($input);

// or
[$valid, $data, $errors] = $schema->tryValidate($input);
```
