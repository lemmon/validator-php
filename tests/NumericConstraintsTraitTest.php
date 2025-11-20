<?php

use Lemmon\Validator\Validator;

it('should handle mixed numeric constraints via trait', function () {
    // Test that int validator works with float constraints (via union types)
    $intValidator = Validator::isInt()->min(1.5)->max(10.5);
    expect($intValidator->validate(5))->toBe(5);

    // Test that float validator works with int constraints
    $floatValidator = Validator::isFloat()->min(1)->max(10);
    expect($floatValidator->validate(5.5))->toBe(5.5);

    // Test multipleOf works correctly for both
    $intMultiple = Validator::isInt()->multipleOf(3);
    expect($intMultiple->validate(9))->toBe(9);

    $floatMultiple = Validator::isFloat()->multipleOf(2.5);
    expect($floatMultiple->validate(7.5))->toBe(7.5);
});

it('should support comparison helpers and clamp across numeric validators', function () {
    $intValidator = Validator::isInt()->clampToRange(1, 2)->gt(0)->lte(3);
    expect($intValidator->validate(3))->toBe(2);

    $floatValidator = Validator::isFloat()->clampToRange(1.6, 2.4)->gt(0)->lt(3);
    expect($floatValidator->validate(1.4))->toBe(1.6);
    expect($floatValidator->validate(2.45))->toBe(2.4);
});

it('should throw when clamp is misconfigured', function () {
    Validator::isInt()->clampToRange(5, 1);
})->throws(InvalidArgumentException::class, 'Minimum cannot be greater than maximum for clamp');
