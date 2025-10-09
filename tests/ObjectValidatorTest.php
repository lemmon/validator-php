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

it('should include null properties in validated object result', function () {
    $schema = Validator::isObject([
        'name' => Validator::isString()->nullifyEmpty(),
        'age' => Validator::isInt()->coerce()->nullifyEmpty(),
        'active' => Validator::isBool()->coerce(),
    ]);

    $input = (object)[
        'name' => null,
        'age' => null,
        'active' => null,
    ];

    $data = $schema->validate($input);

    // Should include all properties, even if they are null
    expect($data)->toHaveProperty('name', null);
    expect($data)->toHaveProperty('age', null);
    expect($data)->toHaveProperty('active', null);

    // Verify the object structure
    $expected = new stdClass();
    $expected->name = null;
    $expected->age = null;
    $expected->active = null;

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
        expect($e->getErrors())->toBe(['Input must be an object']);
        return;
    }

    $this->fail('ValidationException was not thrown');
});
