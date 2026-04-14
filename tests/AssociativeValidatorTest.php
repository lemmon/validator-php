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

it('should include fields with defaultUsing values even when not provided', function () {
    $schema = Validator::isAssociative([
        'name' => Validator::isString()->required(),
        'meta' => Validator::isObject()->defaultUsing(static fn() => (object) ['active' => true]),
    ]);

    $data = $schema->validate([
        'name' => 'John Doe',
    ]);

    expect($data['name'])->toBe('John Doe');
    expect($data['meta'])->toEqual((object) ['active' => true]);
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

    expect(fn() => $schema->validate($input))
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

    expect(fn() => $schema->validate('not-empty'))
        ->toThrow(ValidationException::class, 'Input must be an associative array');
});

it('should remap output key with outputKey when field is provided', function () {
    $schema = Validator::isAssociative([
        'service_id' => Validator::isString()->uuid()->outputKey('service'),
        'user_id' => Validator::isString()->uuid()->outputKey('user'),
    ]);

    $input = [
        'service_id' => '550e8400-e29b-41d4-a716-446655440000',
        'user_id' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
    ];

    $data = $schema->validate($input);

    expect($data)->toBe([
        'service' => '550e8400-e29b-41d4-a716-446655440000',
        'user' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
    ]);
    expect($data)->not->toHaveKey('service_id');
    expect($data)->not->toHaveKey('user_id');
});

it('should remap output key with outputKey combined with transform', function () {
    $schema = Validator::isAssociative([
        'service_id' => Validator::isString()
            ->uuid()
            ->transform(fn(string $id) => ['id' => $id, 'type' => 'service'])
            ->outputKey('service'),
    ]);

    $input = [
        'service_id' => '550e8400-e29b-41d4-a716-446655440000',
    ];

    $data = $schema->validate($input);

    expect($data)->toBe([
        'service' => ['id' => '550e8400-e29b-41d4-a716-446655440000', 'type' => 'service'],
    ]);
});

it('should remap output key for fields with default values', function () {
    $schema = Validator::isAssociative([
        'level' => Validator::isInt()
            ->coerce()
            ->default(3)
            ->outputKey('tier'),
    ])->coerceAll();

    $data = $schema->validate([]);

    expect($data)->toBe(['tier' => 3]);
    expect($data)->not->toHaveKey('level');
});

it('should preserve undeclared keys when passthrough is enabled', function () {
    $schema = Validator::isAssociative([
        'name' => Validator::isString()->required(),
    ])->passthrough();

    $input = [
        'name' => 'Ann',
        'metadata' => ['deep' => true],
        'extra' => 1,
    ];

    $data = $schema->validate($input);

    expect($data['name'])->toBe('Ann');
    expect($data['metadata'])->toBe(['deep' => true]);
    expect($data['extra'])->toBe(1);
});

it('should pass through all keys when schema is empty and passthrough is enabled', function () {
    $schema = Validator::isAssociative([])->passthrough();

    $input = [
        'a' => 1,
        'nested' => ['b' => 2],
    ];

    expect($schema->validate($input))->toBe($input);
});

it('should not let passthrough overwrite outputKey targets', function () {
    $schema = Validator::isAssociative([
        'service_id' => Validator::isString()->uuid()->outputKey('service'),
    ])->passthrough();

    $input = [
        'service_id' => '550e8400-e29b-41d4-a716-446655440000',
        'service' => 'unvalidated-should-not-win',
    ];

    $data = $schema->validate($input);

    expect($data['service'])->toBe('550e8400-e29b-41d4-a716-446655440000');
});

it('should not mutate shared field validators when using coerceAll', function () {
    $shared = Validator::isInt();
    $withCoerceAll = Validator::isAssociative([
        'n' => $shared,
    ])->coerceAll();
    $withoutCoerceAll = Validator::isAssociative([
        'n' => $shared,
    ]);

    expect($withCoerceAll->validate(['n' => '7']))->toBe(['n' => 7]);
    expect(fn() => $withoutCoerceAll->validate(['n' => '7']))->toThrow(ValidationException::class);
});

it('should preserve coerceAll behavior when cloning an associative schema', function () {
    $original = Validator::isAssociative([
        'n' => Validator::isInt(),
    ])->coerceAll();
    $copy = $original->clone();

    expect($copy->validate(['n' => '2']))->toBe(['n' => 2]);
});

it('should recursively coerce nested associative schemas with coerceAll', function () {
    $schema = Validator::isAssociative([
        'name' => Validator::isString(),
        'age' => Validator::isInt(),
        'address' => Validator::isAssociative([
            'zip' => Validator::isInt(),
            'active' => Validator::isBool(),
        ]),
    ])->coerceAll();

    $result = $schema->validate([
        'name' => 'Jane',
        'age' => '30',
        'address' => [
            'zip' => '90210',
            'active' => 'true',
        ],
    ]);

    expect($result['age'])->toBe(30);
    expect($result['address']['zip'])->toBe(90_210);
    expect($result['address']['active'])->toBe(true);
});

it('should recursively coerce array items through coerceAll', function () {
    $schema = Validator::isAssociative([
        'tags' => Validator::isArray()->items(Validator::isInt()),
    ])->coerceAll();

    $result = $schema->validate([
        'tags' => ['1', '2', '3'],
    ]);

    expect($result['tags'])->toBe([1, 2, 3]);
});

it('should recursively coerce deeply nested schemas with coerceAll', function () {
    $schema = Validator::isAssociative([
        'level1' => Validator::isAssociative([
            'level2' => Validator::isAssociative([
                'value' => Validator::isInt(),
            ]),
        ]),
    ])->coerceAll();

    $result = $schema->validate([
        'level1' => [
            'level2' => [
                'value' => '42',
            ],
        ],
    ]);

    expect($result['level1']['level2']['value'])->toBe(42);
});

it('should recursively coerce array items that are schemas with coerceAll', function () {
    $schema = Validator::isAssociative([
        'items' => Validator::isArray()->items(
            Validator::isAssociative([
                'qty' => Validator::isInt(),
                'price' => Validator::isFloat(),
            ]),
        ),
    ])->coerceAll();

    $result = $schema->validate([
        'items' => [
            ['qty' => '2', 'price' => '9.99'],
            ['qty' => '1', 'price' => '4.50'],
        ],
    ]);

    expect($result['items'][0]['qty'])->toBe(2);
    expect($result['items'][0]['price'])->toBe(9.99);
    expect($result['items'][1]['qty'])->toBe(1);
});

it('should coerce top-level stdClass input when coerceAll is enabled', function () {
    $schema = Validator::isAssociative([
        'name' => Validator::isString(),
    ])->coerceAll();

    $result = $schema->validate((object) ['name' => 'Alice']);
    expect($result)->toBe(['name' => 'Alice']);
});

it('should snapshot schema validators so later mutations do not leak', function () {
    $shared = Validator::isInt()->min(10);
    $schema = Validator::isAssociative(['n' => $shared]);

    $shared->min(100);

    $result = $schema->validate(['n' => 50]);
    expect($result)->toBe(['n' => 50]);
});
