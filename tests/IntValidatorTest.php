<?php

use Lemmon\Validator;

it('should coerce empty string to zero', function () {
    $validator = Validator::isInt()->coerce();

    expect($validator->validate(''))->toBe(0);
});

it('should coerce numeric strings to integers', function () {
    $validator = Validator::isInt()->coerce();

    expect($validator->validate('123'))->toBe(123);
    expect($validator->validate('0'))->toBe(0);
    expect($validator->validate('-42'))->toBe(-42);
});

it('should fail coercion for non-numeric strings', function () {
    $validator = Validator::isInt()->coerce();

    $validator->validate('abc');
})->throws(Lemmon\ValidationException::class, 'Value must be an integer');

it('should validate integer ranges', function () {
    $rangeValidator = Validator::isInt()->min(10)->max(100);

    expect($rangeValidator->validate(50))->toBe(50);
    expect($rangeValidator->validate(10))->toBe(10);
    expect($rangeValidator->validate(100))->toBe(100);

    $rangeValidator->validate(5);
})->throws(Lemmon\ValidationException::class, 'Value must be at least 10');

it('should validate integer multiples', function () {
    $multipleValidator = Validator::isInt()->multipleOf(5);

    expect($multipleValidator->validate(15))->toBe(15);
    expect($multipleValidator->validate(20))->toBe(20);
    expect($multipleValidator->validate(0))->toBe(0);

    $multipleValidator->validate(13);
})->throws(Lemmon\ValidationException::class, 'Value must be a multiple of 5');

it('should validate positive integers', function () {
    $positiveValidator = Validator::isInt()->positive();

    expect($positiveValidator->validate(1))->toBe(1);
    expect($positiveValidator->validate(100))->toBe(100);

    $positiveValidator->validate(-1);
})->throws(Lemmon\ValidationException::class, 'Value must be positive');

it('should validate negative integers', function () {
    $negativeValidator = Validator::isInt()->negative();

    expect($negativeValidator->validate(-1))->toBe(-1);
    expect($negativeValidator->validate(-100))->toBe(-100);

    $negativeValidator->validate(1);
})->throws(Lemmon\ValidationException::class, 'Value must be negative');

it('should coerce strings to integers', function () {
    $intValidator = Validator::isInt()->coerce();

    expect($intValidator->validate('42'))->toBe(42);
    expect($intValidator->validate('0'))->toBe(0);
    expect($intValidator->validate('-5'))->toBe(-5);
});
