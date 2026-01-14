<?php

declare(strict_types=1);

use Lemmon\Validator\ValidationException;
use Lemmon\Validator\Validator;

it('should validate a correct payload', function () {
    $schema = Validator::isAssociative([
        'required' => Validator::isString()->required(),
        'optional' => Validator::isString(),
        'forced' => Validator::isString()->default('Hello!'),
        'level' => Validator::isInt()
            ->coerce()
            ->in([3, 5, 8])
            ->default(3),
        'override' => Validator::isBool()->coerce()->default(false),
    ])->coerceAll();

    $input = [
        'required' => 'test',
        'level' => '5',
    ];

    $data = $schema->validate($input);

    expect($data)->toBe([
        'required' => 'test',
        'forced' => 'Hello!',
        'level' => 5,
        'override' => false,
    ]);
});

it('should throw a validation exception for invalid payload', function () {
    $schema = Validator::isAssociative([
        'required' => Validator::isString()->required(),
        'level' => Validator::isInt()->in([3, 5, 8]),
    ]);

    $input = [
        'level' => 10,
    ];

    $schema->validate($input);
})->throws(ValidationException::class);

it('should throw a validation exception for non-array input in AssociativeValidator', function () {
    $schema = Validator::isAssociative([]);

    try {
        $schema->validate('not an array');
    } catch (ValidationException $e) {
        expect($e->getErrors())->toBe(['Input must be an associative array']);
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
    $schema = Validator::isAssociative([
        'name' => Validator::isString(),
        'age' => Validator::isInt(),
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
    $schema = Validator::isAssociative([
        'name' => Validator::isString(),
    ]);

    $object = new stdClass();
    $object->name = 'John Doe';

    $schema->validate($object);
})->throws(ValidationException::class, 'Input must be an associative array');

it('should only include provided fields in result (not all schema fields)', function () {
    $schema = Validator::isAssociative([
        'name' => Validator::isString(),
        'email' => Validator::isString()->email(),
        'age' => Validator::isInt()->coerce(),
        'city' => Validator::isString(),
        'country' => Validator::isString(),
    ]);

    // Only provide 2 out of 5 schema fields
    $input = [
        'name' => 'John Doe',
        'age' => '30',
    ];

    $data = $schema->validate($input);

    // Result should only contain the 2 provided fields
    expect($data)->toBe([
        'name' => 'John Doe',
        'age' => 30,
    ]);

    // Verify no extra keys
    expect($data)->toHaveCount(2);
    expect($data)->toHaveKey('name');
    expect($data)->toHaveKey('age');
    expect($data)->not->toHaveKey('email');
    expect($data)->not->toHaveKey('city');
    expect($data)->not->toHaveKey('country');
});

it('should include fields with default values even when not provided', function () {
    $schema = Validator::isAssociative([
        'name' => Validator::isString()->required(),
        'email' => Validator::isString()->email(),
        'age' => Validator::isInt()->default(25),
        'active' => Validator::isBool()->default(true),
        'city' => Validator::isString(), // No default
    ]);

    // Only provide required field
    $input = [
        'name' => 'John Doe',
    ];

    $data = $schema->validate($input);

    // Should include provided field + fields with defaults
    expect($data)->toBe([
        'name' => 'John Doe',
        'age' => 25, // Default applied
        'active' => true, // Default applied
    ]);

    // Verify correct keys
    expect($data)->toHaveCount(3);
    expect($data)->toHaveKey('name');
    expect($data)->toHaveKey('age');
    expect($data)->toHaveKey('active');
    expect($data)->not->toHaveKey('email'); // Not provided, no default
    expect($data)->not->toHaveKey('city'); // Not provided, no default
});

it('should still validate required fields even when not provided', function () {
    $schema = Validator::isAssociative([
        'name' => Validator::isString()->required(),
        'email' => Validator::isString()->email()->required(),
        'age' => Validator::isInt(),
    ]);

    // Missing required email field
    $input = [
        'name' => 'John Doe',
    ];

    expect(fn () => $schema->validate($input))
        ->toThrow(ValidationException::class, 'Value is required');
});

it('should coerce empty string to empty array when coerce is enabled', function () {
    $schema = Validator::isAssociative()->coerce();

    $result = $schema->validate('');

    expect($result)->toBeArray();
    expect($result)->toHaveCount(0);
});

it('should coerce empty string to array with defaults when schema has defaults', function () {
    $schema = Validator::isAssociative([
        'name' => Validator::isString(),
        'status' => Validator::isString()->default('active'),
        'role' => Validator::isString()->default('user'),
    ])->coerce();

    $result = $schema->validate('');

    expect($result)->toBeArray();
    expect($result)->toHaveKey('status');
    expect($result)->toHaveKey('role');
    expect($result['status'])->toBe('active');
    expect($result['role'])->toBe('user');
    expect($result)->not->toHaveKey('name'); // Not provided, no default
});

it('should reject non-empty strings even with coerce enabled', function () {
    $schema = Validator::isAssociative()->coerce();

    expect(fn () => $schema->validate('not-empty'))
        ->toThrow(ValidationException::class, 'Input must be an associative array');
});
