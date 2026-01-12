<?php

declare(strict_types=1);

use Lemmon\Validator\ValidationException;
use Lemmon\Validator\Validator;

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

it('should validate non-empty arrays', function () {
    $validator = Validator::isArray()->notEmpty();

    expect($validator->validate(['value']))->toBe(['value']);

    $validator->validate([]);
})->throws(ValidationException::class, 'Value must not be empty');

it('should use custom error message for notEmpty array validation', function () {
    $validator = Validator::isArray()->notEmpty('Array cannot be empty');
    $validator->validate([]);
})->throws(ValidationException::class, 'Array cannot be empty');

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

it('should validate minimum items constraint', function () {
    $validator = Validator::isArray()->minItems(3);

    expect($validator->validate([1, 2, 3]))->toBe([1, 2, 3]);
    expect($validator->validate([1, 2, 3, 4]))->toBe([1, 2, 3, 4]);

    $validator->validate([1, 2]);
})->throws(ValidationException::class, 'Value must contain at least 3 items');

it('should validate maximum items constraint', function () {
    $validator = Validator::isArray()->maxItems(3);

    expect($validator->validate([1, 2, 3]))->toBe([1, 2, 3]);
    expect($validator->validate([1, 2]))->toBe([1, 2]);

    $validator->validate([1, 2, 3, 4]);
})->throws(ValidationException::class, 'Value must contain at most 3 items');

it('should validate minItems and maxItems together', function () {
    $validator = Validator::isArray()->minItems(2)->maxItems(4);

    expect($validator->validate([1, 2]))->toBe([1, 2]);
    expect($validator->validate([1, 2, 3, 4]))->toBe([1, 2, 3, 4]);

    $validator->validate([1]);
})->throws(ValidationException::class, 'Value must contain at least 2 items');

it('should validate maxItems constraint when exceeded', function () {
    $validator = Validator::isArray()->maxItems(4);
    $validator->validate([1, 2, 3, 4, 5]);
})->throws(ValidationException::class, 'Value must contain at most 4 items');

it('should use custom error message for minItems', function () {
    $validator = Validator::isArray()->minItems(3, 'Array must have at least 3 elements');
    $validator->validate([1, 2]);
})->throws(ValidationException::class, 'Array must have at least 3 elements');

it('should use custom error message for maxItems', function () {
    $validator = Validator::isArray()->maxItems(2, 'Array must have at most 2 elements');
    $validator->validate([1, 2, 3]);
})->throws(ValidationException::class, 'Array must have at most 2 elements');

it('should validate array contains specific value', function () {
    $validator = Validator::isArray()->contains('banana');

    expect($validator->validate(['apple', 'banana', 'cherry']))->toBe([
        'apple',
        'banana',
        'cherry',
    ]);

    $validator->validate(['apple', 'cherry']);
})->throws(ValidationException::class, 'Value must contain the required item');

it('should validate array contains value with strict comparison', function () {
    $validator = Validator::isArray()->contains(0);

    // Should find integer 0, not string '0'
    expect($validator->validate([0, 1, 2]))->toBe([0, 1, 2]);
    $validator->validate(['0', 1, 2]);
})->throws(ValidationException::class);

it('should validate array contains item matching validator', function () {
    $validator = Validator::isArray()->contains(Validator::isString()->email());

    expect($validator->validate(['not-email', 'test@example.com', 'also-not-email']))->toBe([
        'not-email',
        'test@example.com',
        'also-not-email',
    ]);

    $validator->validate(['not-email', 'also-not-email']);
})->throws(ValidationException::class, 'Value must contain the required item');

it('should validate array contains item matching complex validator', function () {
    $validator = Validator::isArray()->contains(Validator::isInt()->positive());

    expect($validator->validate([-1, 0, 5, -2]))->toBe([-1, 0, 5, -2]);

    $validator->validate([-1, 0, -2]);
})->throws(ValidationException::class);

it('should use custom error message for contains', function () {
    $validator = Validator::isArray()->contains('required', 'Array must contain "required"');
    try {
        $validator->validate(['other', 'values']);
        expect(false)->toBe(true); // Should not reach here
    } catch (ValidationException $e) {
        expect($e->getErrors())->toContain('Array must contain "required"');
    }
});

it('should work with contains and item validator together', function () {
    $validator = Validator::isArray()->items(Validator::isString())->contains('banana');

    expect($validator->validate(['apple', 'banana', 'cherry']))->toBe([
        'apple',
        'banana',
        'cherry',
    ]);

    // Should fail item validation first
    $validator->validate(['apple', 123, 'banana']);
})->throws(ValidationException::class);
