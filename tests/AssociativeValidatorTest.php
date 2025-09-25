<?php

use Lemmon\Validator;

it('should validate a correct payload', function () {
    $schema = Validator::isAssociative([
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
    $schema = Validator::isAssociative([
        'required' => Validator::isString()->required(),
        'level'    => Validator::isInt()->oneOf([3, 5, 8]),
    ]);

    $input = [
        'level' => 10,
    ];

    $schema->validate($input);
})->throws(Lemmon\ValidationException::class);

it('should throw a validation exception for non-array input in AssociativeValidator', function () {
    $schema = Validator::isAssociative([]);

    try {
        $schema->validate('not an array');
    } catch (Lemmon\ValidationException $e) {
        expect($e->getErrors())->toBe(['Input must be an associative array.']);
    }
});

it('should validate an empty associative array with an empty schema', function () {
    $schema = Validator::isAssociative([]);
    $data = $schema->validate([]);
    expect($data)->toBe([]);
});

it('should allow Validator::isAssociative() to be called without arguments', function () {
    $schema = Validator::isAssociative();
    $data = $schema->validate([]);
    expect($data)->toBe([]);
});

it('should handle null input for AssociativeValidator created without arguments gracefully', function () {
    $schema = Validator::isAssociative();
    [$valid, $data, $errors] = $schema->tryValidate(null);
    expect($valid)->toBe(true);
    expect($data)->toBe(null);
    expect($errors)->toBe(null);
});

it('should allow null for optional associative array validator', function () {
    $validator = Validator::isAssociative();
    $data = $validator->validate(null);
    expect($data)->toBe(null);
});

it('should allow null for optional nested associative array in schema', function () {
    $schema = Validator::isAssociative([
        'nested' => Validator::isAssociative(),
    ]);

    $input = [
        'nested' => null,
    ];

    $data = $schema->validate($input);
    expect($data)->toBe(['nested' => null]);
});

it('should coerce stdClass object to associative array when coerce is enabled', function () {
    $schema = Lemmon\Validator::isAssociative([
        'name' => Lemmon\Validator::isString(),
        'age' => Lemmon\Validator::isInt(),
    ])->coerce();

    $object = new stdClass();
    $object->name = 'John Doe';
    $object->age = 42;

    $validated = $schema->validate($object);

    expect($validated)->toBe([
        'name' => 'John Doe',
        'age' => 42,
    ]);
});

it('should fail to validate stdClass object when coerce is not enabled', function () {
    $schema = Lemmon\Validator::isAssociative([
        'name' => Lemmon\Validator::isString(),
    ]);

    $object = new stdClass();
    $object->name = 'John Doe';

    $schema->validate($object);
})->throws(Lemmon\ValidationException::class, 'Input must be an associative array.');
