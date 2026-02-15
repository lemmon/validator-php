<?php

declare(strict_types=1);

use Lemmon\Validator\ValidationException;
use Lemmon\Validator\Validator;

it('should validate a stdClass object', function () {
    $schema = Validator::isObject([
        'name' => Validator::isString(),
        'age' => Validator::isInt()->coerce(),
    ]);

    $input = (object) [
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

    $input = (object) [
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
    } catch (Lemmon\Validator\ValidationException $e) {
        expect($e->getErrors())->toBe(['Input must be an object']);
        return;
    }

    $this->fail('ValidationException was not thrown');
});

it('should only include provided fields in result (not all schema fields)', function () {
    $schema = Validator::isObject([
        'name' => Validator::isString(),
        'email' => Validator::isString()->email(),
        'age' => Validator::isInt()->coerce(),
        'city' => Validator::isString(),
        'country' => Validator::isString(),
    ]);

    // Only provide 2 out of 5 schema fields
    $input = (object) [
        'name' => 'John Doe',
        'age' => '30',
    ];

    $data = $schema->validate($input);

    // Result should only contain the 2 provided fields
    $expected = new stdClass();
    $expected->name = 'John Doe';
    $expected->age = 30;

    expect($data)->toEqual($expected);

    // Verify no extra properties
    expect(get_object_vars($data))->toHaveCount(2);
    expect($data)->toHaveProperty('name');
    expect($data)->toHaveProperty('age');
    expect($data)->not->toHaveProperty('email');
    expect($data)->not->toHaveProperty('city');
    expect($data)->not->toHaveProperty('country');
});

it('should include fields with default values even when not provided', function () {
    $schema = Validator::isObject([
        'name' => Validator::isString()->required(),
        'email' => Validator::isString()->email(),
        'age' => Validator::isInt()->default(25),
        'active' => Validator::isBool()->default(true),
        'city' => Validator::isString(), // No default
    ]);

    // Only provide required field
    $input = (object) [
        'name' => 'John Doe',
    ];

    $data = $schema->validate($input);

    // Should include provided field + fields with defaults
    $expected = new stdClass();
    $expected->name = 'John Doe';
    $expected->age = 25; // Default applied
    $expected->active = true; // Default applied

    expect($data)->toEqual($expected);

    // Verify correct properties
    expect(get_object_vars($data))->toHaveCount(3);
    expect($data)->toHaveProperty('name', 'John Doe');
    expect($data)->toHaveProperty('age', 25);
    expect($data)->toHaveProperty('active', true);
    expect($data)->not->toHaveProperty('email'); // Not provided, no default
    expect($data)->not->toHaveProperty('city'); // Not provided, no default
});

it('should still validate required fields even when not provided', function () {
    $schema = Validator::isObject([
        'name' => Validator::isString()->required(),
        'email' => Validator::isString()->email()->required(),
        'age' => Validator::isInt(),
    ]);

    // Missing required email field
    $input = (object) [
        'name' => 'John Doe',
    ];

    expect(fn() => $schema->validate($input))
        ->toThrow(ValidationException::class, 'Value is required');
});

it('should coerce empty string to empty object when coerce is enabled', function () {
    $schema = Validator::isObject()->coerce();

    $result = $schema->validate('');

    expect($result)->toBeInstanceOf(stdClass::class);
    expect(get_object_vars($result))->toHaveCount(0);
});

it('should coerce empty string to object with defaults when schema has defaults', function () {
    $schema = Validator::isObject([
        'name' => Validator::isString(),
        'status' => Validator::isString()->default('active'),
        'role' => Validator::isString()->default('user'),
    ])->coerce();

    $result = $schema->validate('');

    expect($result)->toBeInstanceOf(stdClass::class);
    expect($result)->toHaveProperty('status', 'active');
    expect($result)->toHaveProperty('role', 'user');
    expect($result)->not->toHaveProperty('name'); // Not provided, no default
});

it('should reject non-empty strings even with coerce enabled', function () {
    $schema = Validator::isObject()->coerce();

    expect(fn() => $schema->validate('not-empty'))
        ->toThrow(ValidationException::class, 'Input must be an object');
});
