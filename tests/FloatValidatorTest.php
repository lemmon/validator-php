<?php

use Lemmon\Validator;

it('should coerce empty string to zero', function () {
    $validator = Validator::isFloat()->coerce();

    expect($validator->validate(''))->toBe(0.0);
});

it('should coerce numeric strings to floats', function () {
    $validator = Validator::isFloat()->coerce();

    expect($validator->validate('123.45'))->toBe(123.45);
    expect($validator->validate('0'))->toBe(0.0);
    expect($validator->validate('-42.7'))->toBe(-42.7);
    expect($validator->validate('123'))->toBe(123.0);
});

it('should fail coercion for non-numeric strings', function () {
    $validator = Validator::isFloat()->coerce();

    $validator->validate('abc');
})->throws(Lemmon\ValidationException::class, 'Value must be a float');

it('should validate floats', function () {
    $validator = Validator::isFloat();

    expect($validator->validate(42.5))->toBe(42.5);
    expect($validator->validate(100))->toBe(100.0);
    expect($validator->validate('123.45'))->toBe(123.45);

    $validator->validate('not-a-float');
})->throws(Lemmon\ValidationException::class, 'Value must be a float');

it('should validate float ranges', function () {
    $rangeValidator = Validator::isFloat()->min(10)->max(100);

    expect($rangeValidator->validate(50))->toBe(50.0);
    expect($rangeValidator->validate(10))->toBe(10.0);
    expect($rangeValidator->validate(100))->toBe(100.0);

    $rangeValidator->validate(5);
})->throws(Lemmon\ValidationException::class);

it('should validate float multiples', function () {
    $multipleValidator = Validator::isFloat()->multipleOf(5);

    expect($multipleValidator->validate(15))->toBe(15.0);
    expect($multipleValidator->validate(20))->toBe(20.0);

    $multipleValidator->validate(13);
})->throws(Lemmon\ValidationException::class, 'Value must be a multiple of 5');

it('should validate positive floats', function () {
    $positiveValidator = Validator::isFloat()->positive();

    expect($positiveValidator->validate(1))->toBe(1.0);
    expect($positiveValidator->validate(0.1))->toBe(0.1);

    $positiveValidator->validate(-1);
})->throws(Lemmon\ValidationException::class, 'Value must be positive');
