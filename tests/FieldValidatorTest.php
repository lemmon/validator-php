<?php

use Lemmon\Validator;

it('should apply single transformation after validation', function () {
    $validator = Validator::isString()->transform('trim');

    $result = $validator->validate('  hello  ');
    expect($result)->toBe('hello');
});

it('should apply multiple transformations in sequence', function () {
    $validator = Validator::isString()
        ->transform('trim')
        ->transform('strtoupper');

    $result = $validator->validate('  hello world  ');
    expect($result)->toBe('HELLO WORLD');
});

it('should apply transformations using pipe method', function () {
    $validator = Validator::isString()
        ->pipe('trim', 'strtolower', fn($v) => ucfirst($v));

    $result = $validator->validate('  HELLO WORLD  ');
    expect($result)->toBe('Hello world');
});

it('should apply transformations with custom functions', function () {
    $validator = Validator::isInt()
        ->coerce()
        ->transform(fn($v) => $v * 2)
        ->transform(fn($v) => $v + 10);

    $result = $validator->validate('5');
    expect($result)->toBe(20);
});

it('should apply transformations after all validations pass', function () {
    $validator = Validator::isString()
        ->minLength(3)
        ->maxLength(10)
        ->transform('strtoupper');

    $result = $validator->validate('hello');
    expect($result)->toBe('HELLO');
});

it('should not apply transformations when validation fails', function () {
    $validator = Validator::isString()
        ->minLength(10)
        ->transform('strtoupper');

    [$valid, $data, $errors] = $validator->tryValidate('short');

    expect($valid)->toBe(false);
    expect($data)->toBe('short'); // Original value, not transformed
    expect($errors)->toContain('Value must be at least 10 characters long');
});

it('should work with array transformations', function () {
    $validator = Validator::isArray()
        ->filterEmpty()
        ->transform(fn($v) => array_map('strtoupper', $v));

    $result = $validator->validate(['hello', '', 'world', null]);
    expect($result)->toBe(['HELLO', 'WORLD']);
});

it('should handle transformation exceptions gracefully', function () {
    $validator = Validator::isString()
        ->transform(function($v) {
            throw new Exception('Transformation failed');
        });

    expect(fn() => $validator->validate('test'))
        ->toThrow(Exception::class, 'Transformation failed');
});

// Type-aware transformation tests
it('should maintain indexed array structure with pipe operations', function () {
    $validator = Validator::isArray()->pipe('array_unique');

    $result = $validator->validate(['a', 'b', 'a', 'c']);

    expect($result)->toBe(['a', 'b', 'c']);
    expect(array_keys($result))->toBe([0, 1, 2]); // Properly reindexed
});

it('should preserve associative array keys with pipe operations', function () {
    $validator = Validator::isAssociative(['name' => Validator::isString()])
        ->pipe(fn($v) => array_filter($v));

    $result = $validator->validate(['name' => 'John', 'empty' => '']);

    expect($result)->toBe(['name' => 'John']); // Keys preserved
});

it('should handle type transitions from array to string', function () {
    $validator = Validator::isArray()
        ->pipe('array_unique', 'array_reverse')
        ->transform(fn($v) => implode(',', $v))
        ->pipe('trim', 'strtoupper');

    $result = $validator->validate(['a', 'b', 'a', 'c']);

    expect($result)->toBe('C,B,A');
});

it('should handle type transitions from string to array to int', function () {
    $validator = Validator::isString()
        ->pipe('trim')
        ->transform(fn($v) => explode(',', $v))
        ->pipe('array_unique')
        ->transform('count');

    $result = $validator->validate('  a,b,a,c  ');

    expect($result)->toBe(3);
});

it('should handle complex multi-type transformation chains', function () {
    $validator = Validator::isArray()
        ->pipe('array_unique', 'array_reverse')        // Array operations
        ->transform(fn($v) => implode(',', $v))        // Array → String
        ->pipe('trim', 'strtoupper')                   // String operations
        ->transform('strlen')                          // String → Int
        ->transform(fn($v) => $v * 2);                 // Int operations

    $result = $validator->validate(['a', 'b', 'a']);

    expect($result)->toBe(6); // Length of "B,A" (3) * 2
});

it('should handle array pipe operations that break indexing', function () {
    // Test multiple array operations that would break indexing
    $validator = Validator::isArray()
        ->pipe(
            fn($v) => array_filter($v, fn($item) => $item !== 'remove'),
            'array_unique',
            'array_reverse'
        );

    $result = $validator->validate(['a', 'remove', 'b', 'a', 'c']);

    expect($result)->toBe(['c', 'b', 'a']); // Filtered, uniqued, reversed, reindexed
    expect(array_keys($result))->toBe([0, 1, 2]); // Properly indexed
});

it('should handle mixed pipe and transform operations', function () {
    $validator = Validator::isString()
        ->pipe('trim')                                 // String operation
        ->transform(fn($v) => str_split($v))          // String → Array
        ->pipe('array_unique', 'array_reverse')       // Array operations
        ->transform(fn($v) => implode('', $v))        // Array → String
        ->pipe('strtoupper');                         // String operation

    $result = $validator->validate('  hello  ');

    expect($result)->toBe('OLEH'); // Unique chars of "hello" reversed and uppercased
});

it('should work with numeric type transitions', function () {
    $validator = Validator::isString()
        ->transform(fn($v) => (int)$v)                // String → Int
        ->pipe(fn($v) => abs($v))                     // Int operation
        ->transform(fn($v) => (float)$v)              // Int → Float
        ->pipe(fn($v) => $v * 1.5);                   // Float operation

    $result = $validator->validate('-10');

    expect($result)->toBe(15.0); // abs(-10) * 1.5
});

it('should throw a validation exception with a generic message for standalone validators', function () {
    try {
        Validator::isString()->validate(123);
    } catch (Lemmon\ValidationException $e) {
        expect($e->getErrors())->toBe(['Value must be a string']);
    }
});

it('should return a result tuple for standalone validators', function () {
    [$valid, $data, $errors] = Validator::isString()->tryValidate('hello');
    expect($valid)->toBe(true);
    expect($data)->toBe('hello');
    expect($errors)->toBe(null);

    [$valid, $data, $errors] = Validator::isString()->tryValidate(123);
    expect($valid)->toBe(false);
    expect($data)->toBe(123);
    expect($errors)->toBe(['Value must be a string']);
});

it('should collect all validation errors', function () {
    $validator = Validator::isString()->minLength(10)->maxLength(3)->email();

    [$valid, $data, $errors] = $validator->tryValidate('short');

    expect($valid)->toBe(false);
    expect($errors)->toHaveCount(3);
    expect($errors)->toContain('Value must be at least 10 characters long');
    expect($errors)->toContain('Value must be at most 3 characters long');
    expect($errors)->toContain('Value must be a valid email address');
});

it('should pass context to custom validators', function () {
    $validator = Validator::isString()->addValidation(
        function ($value, $key, $input) {
            return $key === 'test' && is_array($input) && isset($input['other']);
        },
        'Custom validation failed'
    );

    [$valid, $data, $errors] = $validator->tryValidate('value', 'test', ['other' => 'data']);
    expect($valid)->toBe(true);

    [$valid, $data, $errors] = $validator->tryValidate('value', 'wrong', ['other' => 'data']);
    expect($valid)->toBe(false);
    expect($errors)->toContain('Custom validation failed');
});

it('should validate allOf combinator', function () {
    $validator = Validator::isString()->allOf([
        Validator::isString()->minLength(3),
        Validator::isString()->maxLength(10),
        Validator::isString()->pattern('/^[a-z]+$/')
    ]);

    expect($validator->validate('hello'))->toBe('hello');

    $validator->validate('hi'); // Too short
})->throws(Lemmon\ValidationException::class, 'Value must satisfy all validation rules');

it('should validate anyOf combinator', function () {
    $validator = Validator::isString()->anyOf([
        Validator::isString()->email(),
        Validator::isString()->url(),
        Validator::isString()->uuid()
    ]);

    expect($validator->validate('test@example.com'))->toBe('test@example.com');
    expect($validator->validate('https://example.com'))->toBe('https://example.com');
    expect($validator->validate('550e8400-e29b-41d4-a716-446655440000'))->toBe('550e8400-e29b-41d4-a716-446655440000');

    $validator->validate('invalid-value');
})->throws(Lemmon\ValidationException::class, 'Value must satisfy at least one validation rule');

it('should validate not combinator', function () {
    $validator = Validator::isString()->not(Validator::isString()->email());

    expect($validator->validate('not-an-email'))->toBe('not-an-email');
    expect($validator->validate('hello world'))->toBe('hello world');

    $validator->validate('test@example.com');
})->throws(Lemmon\ValidationException::class, 'Value must not satisfy the validation rule');
