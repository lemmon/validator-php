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

it('should validate plain arrays', function () {
    $validator = Validator::isArray();

    $data = $validator->validate([1, 2, 3, 'foo']);
    expect($data)->toBe([1, 2, 3, 'foo']);

    $data = $validator->validate(['a', 'b', 'c']);
    expect($data)->toBe(['a', 'b', 'c']);

    $data = $validator->validate([]);
    expect($data)->toBe([]);
});

it('should reject associative arrays', function () {
    $validator = Validator::isArray();

    $validator->validate(['key' => 'value']);
})->throws(Lemmon\ValidationException::class);

it('should validate array items with type validator', function () {
    $validator = Validator::isArray()->items(Validator::isString());

    $data = $validator->validate(['foo', 'bar', 'baz']);
    expect($data)->toBe(['foo', 'bar', 'baz']);
});

it('should reject array items that do not match type validator', function () {
    $validator = Validator::isArray()->items(Validator::isString());

    $validator->validate(['foo', 123, 'baz']);
})->throws(Lemmon\ValidationException::class);

it('should coerce string to single-item array', function () {
    $validator = Validator::isArray()->coerce();

    // Any string becomes single-item array
    $data = $validator->validate('single');
    expect($data)->toBe(['single']);

    $data = $validator->validate('[1,2,3]');
    expect($data)->toBe(['[1,2,3]']);

    $data = $validator->validate('a,b,c');
    expect($data)->toBe(['a,b,c']);
});

it('should coerce associative arrays to indexed arrays', function () {
    $validator = Validator::isArray()->coerce();

    $data = $validator->validate(['key1' => 'value1', 'key2' => 'value2']);
    expect($data)->toBe(['value1', 'value2']);

    $data = $validator->validate(['a' => 1, 'b' => 2, 'c' => 3]);
    expect($data)->toBe([1, 2, 3]);
});

it('should coerce scalar values to array', function () {
    $validator = Validator::isArray()->coerce();

    $data = $validator->validate(123);
    expect($data)->toBe([123]);

    $data = $validator->validate(true);
    expect($data)->toBe([true]);
});

it('should work with required and default values', function () {
    $validator = Validator::isArray()->default(['default']);

    $data = $validator->validate(null, 'test', []);
    expect($data)->toBe(['default']);

    // Test with required
    $validator = Validator::isArray()->required();
    $validator->validate(null, 'test', []);
})->throws(Lemmon\ValidationException::class);

it('should work with oneOf constraint', function () {
    $validator = Validator::isArray()->oneOf([[1, 2], [3, 4]]);

    $data = $validator->validate([1, 2]);
    expect($data)->toBe([1, 2]);

    $validator->validate([1, 2, 3]);
})->throws(Lemmon\ValidationException::class);

it('should handle null correctly with coercion', function () {
    // Without required - null should stay null
    $validator = Validator::isArray()->coerce();
    $data = $validator->validate(null, 'test', []);
    expect($data)->toBe(null);

    // With default - null should use default
    $validator = Validator::isArray()->coerce()->default(['default']);
    $data = $validator->validate(null, 'test', []);
    expect($data)->toBe(['default']);

    // With required - null should throw error
    $validator = Validator::isArray()->coerce()->required();
    $validator->validate(null, 'test', []);
})->throws(Lemmon\ValidationException::class);

it('should throw a validation exception with a generic message for standalone validators', function () {
    try {
        Validator::isString()->validate(123);
    } catch (Lemmon\ValidationException $e) {
        expect($e->getErrors())->toBe(['Value must be a string.']);
    }
});

it('should return a result tuple for standalone validators', function () {
    [$valid, $data, $errors] = Validator::isString()->tryValidate('hello');
    expect($valid)->toBe(true);
    expect($data)->toBe('hello');
    expect($errors)->toBe(null);

    [$valid, $data, $errors] = Validator::isString()->tryValidate(123);
    expect($valid)->toBe(false);
    expect($data)->toBe(123);
    expect($errors)->toBe(['Value must be a string.']);
});

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

it('should allow null for optional array validator', function () {
    $validator = Validator::isArray();
    [$valid, $data, $errors] = $validator->tryValidate(null);
    expect($valid)->toBe(true);
    expect($data)->toBe(null);
    expect($errors)->toBe(null);
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

it('should coerce empty string to empty array', function () {
    $validator = Validator::isArray()->coerce();
    $data = $validator->validate('');
    expect($data)->toBe([]);
});

it('should nullify empty string and empty array when nullifyEmpty is called', function () {
    $stringValidator = Validator::isString()->nullifyEmpty();
    expect($stringValidator->validate(''))->toBe(null);

    $arrayValidator = Validator::isArray()->nullifyEmpty();
    expect($arrayValidator->validate([]))->toBe(null);

    // Should not nullify non-empty values
    expect($stringValidator->validate('hello'))->toBe('hello');
    expect($arrayValidator->validate([1, 2]))->toBe([1, 2]);
    expect($stringValidator->validate(null))->toBe(null);
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
