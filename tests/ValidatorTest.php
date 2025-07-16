<?php

use Lemmon\Validator;

it('should validate a correct payload', function () {
    $schema = Validator::isArray([
        'required' => Validator::isString()->required(),
        'optional' => Validator::isString(),
        'forced'   => Validator::isString()->default('Hello!'),
        'level'    => Validator::isInt()->coerce()->oneOf([3, 5, 8])->default(3),
        'override' => Validator::isBool()->coerce()->default(false),
    ])->coerceAll();

    $input = [
        'required' => 'test',
        'level' => '5',
    ];

    $data = $schema->validate($input);

    expect($data)->toBe([
        'required' => 'test',
        'optional' => null,
        'forced'   => 'Hello!',
        'level'    => 5,
        'override' => false,
    ]);
});

it('should throw a validation exception for invalid payload', function () {
    $schema = Validator::isArray([
        'required' => Validator::isString()->required(),
        'level'    => Validator::isInt()->oneOf([3, 5, 8]),
    ]);

    $input = [
        'level' => 10,
    ];

    $schema->validate($input);
})->throws(Lemmon\ValidationException::class);
