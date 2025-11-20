<?php

use Lemmon\Validator\Validator;
use Lemmon\Validator\ValidationException;

it('should coerce empty string to null for form safety', function () {
    $validator = Validator::isFloat()->coerce();

    expect($validator->validate(''))->toBe(null);
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
})->throws(ValidationException::class, 'Value must be a float');

it('should validate floats', function () {
    $validator = Validator::isFloat();

    expect($validator->validate(42.5))->toBe(42.5);
    expect($validator->validate(100))->toBe(100.0);
    expect($validator->validate('123.45'))->toBe(123.45);

    $validator->validate('not-a-float');
})->throws(ValidationException::class, 'Value must be a float');

it('should validate float ranges', function () {
    $rangeValidator = Validator::isFloat()->min(10)->max(100);

    expect($rangeValidator->validate(50))->toBe(50.0);
    expect($rangeValidator->validate(10))->toBe(10.0);
    expect($rangeValidator->validate(100))->toBe(100.0);

    $rangeValidator->validate(5);
})->throws(ValidationException::class);

it('should validate float multiples', function () {
    $multipleValidator = Validator::isFloat()->multipleOf(5);

    expect($multipleValidator->validate(15))->toBe(15.0);
    expect($multipleValidator->validate(20))->toBe(20.0);

    $multipleValidator->validate(13);
})->throws(ValidationException::class, 'Value must be a multiple of 5');

it('should validate positive floats', function () {
    $positiveValidator = Validator::isFloat()->positive();

    expect($positiveValidator->validate(1))->toBe(1.0);
    expect($positiveValidator->validate(0.1))->toBe(0.1);

    $positiveValidator->validate(-1);
})->throws(ValidationException::class, 'Value must be positive');

it('should validate non-negative and non-positive floats', function () {
    $nonNegative = Validator::isFloat()->nonNegative();
    expect($nonNegative->validate(0.0))->toBe(0.0);
    expect($nonNegative->validate(1.5))->toBe(1.5);
    $nonNegative->validate(-0.1);
})->throws(ValidationException::class, 'Value must be non-negative');

it('should validate comparison helpers on floats', function () {
    $gtLt = Validator::isFloat()->gt(1.5)->lt(2.5);
    expect($gtLt->validate(2.0))->toBe(2.0);
    $gtLt->validate(1.5);
})->throws(ValidationException::class, 'Value must be greater than 1.5');

it('should validate inclusive comparison helpers on floats', function () {
    $gteLte = Validator::isFloat()->gte(10.5)->lte(12.5);
    expect($gteLte->validate(10.5))->toBe(10.5);
    expect($gteLte->validate(12.5))->toBe(12.5);
    $gteLte->validate(12.6);
})->throws(ValidationException::class, 'Value must be at most 12.5');

it('should validate non-positive floats', function () {
    $nonPositive = Validator::isFloat()->nonPositive();
    expect($nonPositive->validate(0.0))->toBe(0.0);
    expect($nonPositive->validate(-2.5))->toBe(-2.5);
    $nonPositive->validate(0.1);
})->throws(ValidationException::class, 'Value must be non-positive');

it('should clamp floats within bounds', function () {
    $clamped = Validator::isFloat()->clampToRange(-1.5, 1.5);

    expect($clamped->validate(-2.0))->toBe(-1.5);
    expect($clamped->validate(2.0))->toBe(1.5);
    expect($clamped->validate(0.5))->toBe(0.5);
});

it('should handle floating-point precision in multipleOf validation', function () {
    // Test cases that previously failed due to floating-point precision
    $validator = Validator::isFloat()->multipleOf(0.01);

    expect($validator->validate(500.01))->toBe(500.01); // Original bug case
    expect($validator->validate(19.99))->toBe(19.99);   // Another precision case
    expect($validator->validate(1234.56))->toBe(1234.56); // Larger number

    // Test with smaller precision
    $smallValidator = Validator::isFloat()->multipleOf(0.001);
    expect($smallValidator->validate(0.999))->toBe(0.999);
    expect($smallValidator->validate(1.001))->toBe(1.001);
});
