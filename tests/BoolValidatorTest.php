<?php

use Lemmon\Validator;

it('should validate boolean values', function () {
    $validator = Validator::isBool();

    expect($validator->validate(true))->toBe(true);
    expect($validator->validate(false))->toBe(false);
});

it('should coerce empty string to null for form safety', function () {
    $validator = Validator::isBool()->coerce();

    expect($validator->validate(''))->toBe(null);
});

it('should coerce string boolean values', function () {
    $validator = Validator::isBool()->coerce();

    expect($validator->validate('true'))->toBe(true);
    expect($validator->validate('false'))->toBe(false);
    expect($validator->validate('on'))->toBe(true);
    expect($validator->validate('off'))->toBe(false);
    expect($validator->validate('1'))->toBe(true);
    expect($validator->validate('0'))->toBe(false);
});

it('should handle case insensitive coercion', function () {
    $validator = Validator::isBool()->coerce();

    expect($validator->validate('TRUE'))->toBe(true);
    expect($validator->validate('FALSE'))->toBe(false);
    expect($validator->validate('ON'))->toBe(true);
    expect($validator->validate('OFF'))->toBe(false);
});

it('should fail validation for non-boolean values without coercion', function () {
    $validator = Validator::isBool();

    expect(fn() => $validator->validate('true'))->toThrow(Lemmon\ValidationException::class);
    expect(fn() => $validator->validate(1))->toThrow(Lemmon\ValidationException::class);
    expect(fn() => $validator->validate(0))->toThrow(Lemmon\ValidationException::class);
});

it('should return non-coercible values as-is for type validation to handle', function () {
    $validator = Validator::isBool()->coerce();

    expect(fn() => $validator->validate('invalid'))->toThrow(Lemmon\ValidationException::class);
    expect(fn() => $validator->validate(123))->toThrow(Lemmon\ValidationException::class);
});
