<?php

use Lemmon\Validator;

it('should validate a stdClass object', function () {
    $schema = Validator::isObject([
        'name' => Validator::isString(),
        'age' => Validator::isInt()->coerce(),
    ]);

    $input = (object)[
        'name' => 'John Doe',
        'age' => '42',
    ];

    $data = $schema->validate($input);

    $expected = new stdClass();
    $expected->name = 'John Doe';
    $expected->age = 42;

    expect($data)->toEqual($expected);
});

it('should coerce an associative array to a stdClass object', function () {
    $schema = Validator::isObject([
        'name' => Validator::isString(),
        'age' => Validator::isInt(),
    ])->coerce();

    $input = [
        'name' => 'Jane Doe',
        'age' => 30,
    ];

    $data = $schema->validate($input);

    $expected = new stdClass();
    $expected->name = 'Jane Doe';
    $expected->age = 30;

    expect($data)->toEqual($expected);
});

it('should fail to validate an associative array when coerce is not enabled', function () {
    $schema = Validator::isObject([
        'name' => Validator::isString(),
    ]);

    $input = ['name' => 'John Doe'];

    try {
        $schema->validate($input);
    } catch (Lemmon\ValidationException $e) {
        expect($e->getErrors())->toBe(['Input must be an object.']);
        return;
    }

    $this->fail('ValidationException was not thrown.');
});
