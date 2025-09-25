<?php

use Lemmon\Validator;

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
