<?php

declare(strict_types=1);

use Lemmon\Validator\ValidationException;
use Lemmon\Validator\Validator;

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
    expect($validator->validate(42))->toBe(42.0);
});

it('should fail coercion for non-numeric strings', function () {
    $validator = Validator::isFloat()->coerce();

    $validator->validate('abc');
})->throws(ValidationException::class, 'Value must be a float');

it('should validate floats', function () {
    $validator = Validator::isFloat();

    expect($validator->validate(42.5))->toBe(42.5);

    $validator->validate('not-a-float');
})->throws(ValidationException::class, 'Value must be a float');

it('should widen integers to floats without coercion', function () {
    $validator = Validator::isFloat();

    expect($validator->validate(100))->toBe(100.0);
    expect($validator->validate(-3))->toBe(-3.0);
    expect($validator->validate(0))->toBe(0.0);
});

it('should reject an int beyond +/-2^53 instead of silently widening it to an imprecise float', function () {
    $validator = Validator::isFloat();

    [$valid, $data] = $validator->tryValidate(2 ** 53 + 1);
    expect($valid)->toBeFalse();
    expect($data)->toBe(2 ** 53 + 1);

    expect($validator->validate(2 ** 53))->toBe((float) 2 ** 53);
});

it('should reject an int beyond +/-2^53 on the coerce() path too, instead of silently widening it', function () {
    $validator = Validator::isFloat()->coerce();

    [$valid, $data] = $validator->tryValidate(2 ** 53 + 1);
    expect($valid)->toBeFalse();
    expect($data)->toBe(2 ** 53 + 1);

    expect($validator->validate(2 ** 53))->toBe((float) 2 ** 53);
});

it('should reject an integer-form string beyond +/-2^53 on the coerce() path, instead of silently widening it', function () {
    $validator = Validator::isFloat()->coerce();

    [$valid, $data] = $validator->tryValidate('9007199254740993');
    expect($valid)->toBeFalse();
    expect($data)->toBe('9007199254740993');

    expect($validator->validate('9007199254740992'))->toBe((float) 2 ** 53);
    expect($validator->validate('-9007199254740992'))->toBe((float) -2 ** 53);

    // Zero-padded and decimal/scientific-notation strings are unaffected by the safe-integer guard.
    expect($validator->validate('007'))->toBe(7.0);
    expect($validator->validate('1e3'))->toBe(1000.0);
});

it('should not let two distinct large ints collapse onto the same widened float in const()/in()', function () {
    // Without a safe-integer guard, both widen to the same float and would false-positive.
    [$valid] = Validator::isFloat()
        ->const(2 ** 53)
        ->tryValidate(2 ** 53 + 1);
    expect($valid)->toBeFalse();

    [$valid] = Validator::isFloat()
        ->const(PHP_INT_MAX)
        ->tryValidate(PHP_INT_MAX - 1);
    expect($valid)->toBeFalse();

    [$valid] = Validator::isFloat()->in([PHP_INT_MAX])->tryValidate(PHP_INT_MAX - 1);
    expect($valid)->toBeFalse();
});

it('should match int() literals in in() after an integer input widens to float', function () {
    $validator = Validator::isFloat()->in([1, 2, 3]);

    expect($validator->validate(2))->toBe(2.0);

    $validator->validate(5);
})->throws(ValidationException::class);

it('should match an int literal in const() after an integer input widens to float', function () {
    $validator = Validator::isFloat()->const(1);

    expect($validator->validate(1))->toBe(1.0);

    $validator->validate(2);
})->throws(ValidationException::class);

it('should reject numeric strings without coercion', function () {
    $validator = Validator::isFloat();

    [$valid, $data] = $validator->tryValidate('123.45');

    expect($valid)->toBeFalse();
    expect($data)->toBe('123.45');
});

it('should validate float ranges', function () {
    $rangeValidator = Validator::isFloat()->min(10)->max(100);

    expect($rangeValidator->validate(50.0))->toBe(50.0);
    expect($rangeValidator->validate(10.0))->toBe(10.0);
    expect($rangeValidator->validate(100.0))->toBe(100.0);

    $rangeValidator->validate(5.0);
})->throws(ValidationException::class);

it('should validate floats between bounds', function () {
    $validator = Validator::isFloat()->between(1.5, 2.5);

    expect($validator->validate(1.5))->toBe(1.5);
    expect($validator->validate(2.0))->toBe(2.0);
    expect($validator->validate(2.5))->toBe(2.5);

    $validator->validate(1.4);
})->throws(ValidationException::class, 'Value must be between 1.5 and 2.5');

it('should reject floats above the between range', function () {
    $validator = Validator::isFloat()->between(1.5, 2.5);
    $validator->validate(2.6);
})->throws(ValidationException::class, 'Value must be between 1.5 and 2.5');

it('should use custom error message for float between validation', function () {
    $validator = Validator::isFloat()->between(1.5, 2.5, 'Out of range');
    $validator->validate(3.0);
})->throws(ValidationException::class, 'Out of range');

it('should validate float multiples', function () {
    $multipleValidator = Validator::isFloat()->multipleOf(5);

    expect($multipleValidator->validate(15.0))->toBe(15.0);
    expect($multipleValidator->validate(20.0))->toBe(20.0);

    $multipleValidator->validate(13.0);
})->throws(ValidationException::class, 'Value must be a multiple of 5');

it('should validate positive floats', function () {
    $positiveValidator = Validator::isFloat()->positive();

    expect($positiveValidator->validate(1.0))->toBe(1.0);
    expect($positiveValidator->validate(0.1))->toBe(0.1);

    $positiveValidator->validate(-1.0);
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
    expect($validator->validate(19.99))->toBe(19.99); // Another precision case
    expect($validator->validate(1234.56))->toBe(1234.56); // Larger number

    // Test with smaller precision
    $smallValidator = Validator::isFloat()->multipleOf(0.001);
    expect($smallValidator->validate(0.999))->toBe(0.999);
    expect($smallValidator->validate(1.001))->toBe(1.001);
});
