<?php

declare(strict_types=1);

use Lemmon\Validator\ValidationException;
use Lemmon\Validator\Validator;

it('should coerce empty string to null for form safety', function () {
    $validator = Validator::isInt()->coerce();

    expect($validator->validate(''))->toBe(null);
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
})->throws(ValidationException::class, 'Value must be an integer');

it('should validate integer ranges', function () {
    $rangeValidator = Validator::isInt()->min(10)->max(100);

    expect($rangeValidator->validate(50))->toBe(50);
    expect($rangeValidator->validate(10))->toBe(10);
    expect($rangeValidator->validate(100))->toBe(100);

    $rangeValidator->validate(5);
})->throws(ValidationException::class, 'Value must be at least 10');

it('should validate integers between bounds', function () {
    $validator = Validator::isInt()->between(10, 20);

    expect($validator->validate(10))->toBe(10);
    expect($validator->validate(15))->toBe(15);
    expect($validator->validate(20))->toBe(20);

    $validator->validate(9);
})->throws(ValidationException::class, 'Value must be between 10 and 20');

it('should reject integers above the between range', function () {
    $validator = Validator::isInt()->between(10, 20);
    $validator->validate(21);
})->throws(ValidationException::class, 'Value must be between 10 and 20');

it('should use custom error message for integer between validation', function () {
    $validator = Validator::isInt()->between(1, 5, 'Out of range');
    $validator->validate(0);
})->throws(ValidationException::class, 'Out of range');

it('should validate integer multiples', function () {
    $multipleValidator = Validator::isInt()->multipleOf(5);

    expect($multipleValidator->validate(15))->toBe(15);
    expect($multipleValidator->validate(20))->toBe(20);
    expect($multipleValidator->validate(0))->toBe(0);

    $multipleValidator->validate(13);
})->throws(ValidationException::class, 'Value must be a multiple of 5');

it('should validate positive integers', function () {
    $positiveValidator = Validator::isInt()->positive();

    expect($positiveValidator->validate(1))->toBe(1);
    expect($positiveValidator->validate(100))->toBe(100);

    $positiveValidator->validate(-1);
})->throws(ValidationException::class, 'Value must be positive');

it('should validate negative integers', function () {
    $negativeValidator = Validator::isInt()->negative();

    expect($negativeValidator->validate(-1))->toBe(-1);
    expect($negativeValidator->validate(-100))->toBe(-100);

    $negativeValidator->validate(1);
})->throws(ValidationException::class, 'Value must be negative');

it('should validate non-negative and non-positive integers', function () {
    $nonNegative = Validator::isInt()->nonNegative();
    expect($nonNegative->validate(0))->toBe(0);
    expect($nonNegative->validate(5))->toBe(5);
    $nonNegative->validate(-1);
})->throws(ValidationException::class, 'Value must be non-negative');

it('should validate comparison helpers on integers', function () {
    $gtLt = Validator::isInt()->gt(10)->lt(20);
    expect($gtLt->validate(15))->toBe(15);
    $gtLt->validate(10);
})->throws(ValidationException::class, 'Value must be greater than 10');

it('should validate inclusive comparison helpers on integers', function () {
    $gteLte = Validator::isInt()->gte(5)->lte(7);
    expect($gteLte->validate(5))->toBe(5);
    expect($gteLte->validate(7))->toBe(7);
    $gteLte->validate(8);
})->throws(ValidationException::class, 'Value must be at most 7');

it('should validate non-positive integers', function () {
    $nonPositive = Validator::isInt()->nonPositive();
    expect($nonPositive->validate(0))->toBe(0);
    expect($nonPositive->validate(-3))->toBe(-3);
    $nonPositive->validate(1);
})->throws(ValidationException::class, 'Value must be non-positive');

it('should clamp integers within bounds', function () {
    $clamped = Validator::isInt()->clampToRange(0, 10);

    expect($clamped->validate(-5))->toBe(0);
    expect($clamped->validate(15))->toBe(10);
    expect($clamped->validate(7))->toBe(7);
});

it('should coerce strings to integers', function () {
    $intValidator = Validator::isInt()->coerce();

    expect($intValidator->validate('42'))->toBe(42);
    expect($intValidator->validate('0'))->toBe(0);
    expect($intValidator->validate('-5'))->toBe(-5);
});

it('should validate port numbers', function () {
    $validator = Validator::isInt()->port();

    // Valid ports
    expect($validator->validate(80))->toBe(80);
    expect($validator->validate(443))->toBe(443);
    expect($validator->validate(3000))->toBe(3000);
    expect($validator->validate(65_535))->toBe(65_535);
    expect($validator->validate(1))->toBe(1);

    // Invalid: out of range
    $validator->validate(0);
})->throws(ValidationException::class, 'Value must be a valid port number (1-65535)');

it('should validate port numbers with coercion', function () {
    $validator = Validator::isInt()->coerce()->port();

    // Valid ports from strings
    expect($validator->validate('80'))->toBe(80);
    expect($validator->validate('443'))->toBe(443);
    expect($validator->validate('3000'))->toBe(3000);
    expect($validator->validate('65535'))->toBe(65_535);
});

it('should reject port 0', function () {
    $validator = Validator::isInt()->port();
    $validator->validate(0);
})->throws(ValidationException::class);

it('should reject ports above 65535', function () {
    $validator = Validator::isInt()->port();
    $validator->validate(65_536);
})->throws(ValidationException::class);

it('should reject negative ports', function () {
    $validator = Validator::isInt()->port();
    $validator->validate(-1);
})->throws(ValidationException::class);

it('should use custom error message for port validation', function () {
    $validator = Validator::isInt()->port('Must be a valid port');
    $validator->validate(0);
})->throws(ValidationException::class, 'Must be a valid port');
