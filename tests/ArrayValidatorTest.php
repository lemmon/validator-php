<?php

use Lemmon\Validator\Validator;
use Lemmon\Validator\ValidationException;

it('should filter empty values and reindex array', function () {
    $validator = Validator::isArray()->filterEmpty();

    $input = ['apple', '', 'banana', null, 'cherry'];
    $result = $validator->validate($input);

    expect($result)->toBe(['apple', 'banana', 'cherry']);
    expect(array_keys($result))->toBe([0, 1, 2]); // Properly reindexed
});

it('should preserve valid falsy values when filtering', function () {
    $validator = Validator::isArray()->filterEmpty();

    $input = ['hello', '', 0, null, false, 'world'];
    $result = $validator->validate($input);

    expect($result)->toBe(['hello', 0, false, 'world']);
    expect(array_keys($result))->toBe([0, 1, 2, 3]); // Properly reindexed
});

it('should filter empty values with item validator', function () {
    $validator = Validator::isArray()->items(Validator::isString())->filterEmpty();

    $input = ['hello', '', 'world', null, 'test'];
    $result = $validator->validate($input);

    expect($result)->toBe(['hello', 'world', 'test']);
    expect(array_keys($result))->toBe([0, 1, 2]); // Properly reindexed
});

it('should handle empty array after filtering', function () {
    $validator = Validator::isArray()->filterEmpty();

    $input = ['', null, '', null];
    $result = $validator->validate($input);

    expect($result)->toBe([]);
});

it('should work without filtering when not enabled', function () {
    $validator = Validator::isArray();

    $input = ['apple', '', 'banana', null, 'cherry'];
    $result = $validator->validate($input);

    expect($result)->toBe(['apple', '', 'banana', null, 'cherry']); // No filtering
});

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
})->throws(ValidationException::class);

it('should validate array items with type validator', function () {
    $validator = Validator::isArray()->items(Validator::isString());

    $data = $validator->validate(['foo', 'bar', 'baz']);
    expect($data)->toBe(['foo', 'bar', 'baz']);
});

it('should reject array items that do not match type validator', function () {
    $validator = Validator::isArray()->items(Validator::isString());

    $validator->validate(['foo', 123, 'baz']);
})->throws(ValidationException::class);

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
})->throws(ValidationException::class);

// Note: oneOf() is not available on ArrayValidator as array comparison doesn't make logical sense.
// Use satisfies() for custom array validation logic if needed.

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
})->throws(ValidationException::class);

it('should allow null for optional array validator', function () {
    $validator = Validator::isArray();
    [$valid, $data, $errors] = $validator->tryValidate(null);
    expect($valid)->toBe(true);
    expect($data)->toBe(null);
    expect($errors)->toBe(null);
});

it('should coerce empty string to empty array', function () {
    $validator = Validator::isArray()->coerce();
    $data = $validator->validate('');
    expect($data)->toBe([]);
});

it('should nullify empty string and empty array when nullifyEmpty is called', function () {
    $arrayValidator = Validator::isArray()->nullifyEmpty();
    expect($arrayValidator->validate([]))->toBe(null);

    // Should not nullify non-empty values
    expect($arrayValidator->validate([1, 2]))->toBe([1, 2]);
});
