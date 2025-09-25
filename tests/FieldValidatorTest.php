<?php

use Lemmon\Validator;

it('should throw a validation exception with a generic message for standalone validators', function () {
    try {
        Validator::isString()->validate(123);
    } catch (Lemmon\ValidationException $e) {
        expect($e->getErrors())->toBe(['Value must be a string.']);
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
    expect($errors)->toBe(['Value must be a string.']);
});

it('should collect all validation errors', function () {
    $validator = Validator::isString()->minLength(10)->maxLength(3)->email();

    [$valid, $data, $errors] = $validator->tryValidate('short');

    expect($valid)->toBe(false);
    expect($errors)->toHaveCount(3);
    expect($errors)->toContain('Value must be at least 10 characters long.');
    expect($errors)->toContain('Value must be at most 3 characters long.');
    expect($errors)->toContain('Value must be a valid email address.');
});

it('should pass context to custom validators', function () {
    $validator = Validator::isString()->addValidation(
        function ($value, $key, $input) {
            return $key === 'test' && is_array($input) && isset($input['other']);
        },
        'Custom validation failed.'
    );

    [$valid, $data, $errors] = $validator->tryValidate('value', 'test', ['other' => 'data']);
    expect($valid)->toBe(true);

    [$valid, $data, $errors] = $validator->tryValidate('value', 'wrong', ['other' => 'data']);
    expect($valid)->toBe(false);
    expect($errors)->toContain('Custom validation failed.');
});

it('should validate allOf combinator', function () {
    $validator = Validator::isString()->allOf([
        Validator::isString()->minLength(3),
        Validator::isString()->maxLength(10),
        Validator::isString()->pattern('/^[a-z]+$/')
    ]);

    expect($validator->validate('hello'))->toBe('hello');

    $validator->validate('hi'); // Too short
})->throws(Lemmon\ValidationException::class, 'Value must satisfy all validation rules.');

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
})->throws(Lemmon\ValidationException::class, 'Value must satisfy at least one validation rule.');

it('should validate not combinator', function () {
    $validator = Validator::isString()->not(Validator::isString()->email());

    expect($validator->validate('not-an-email'))->toBe('not-an-email');
    expect($validator->validate('hello world'))->toBe('hello world');

    $validator->validate('test@example.com');
})->throws(Lemmon\ValidationException::class, 'Value must not satisfy the validation rule.');
