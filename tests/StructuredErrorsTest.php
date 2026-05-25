<?php

declare(strict_types=1);

use Lemmon\Validator\ValidationCode;
use Lemmon\Validator\ValidationError;
use Lemmon\Validator\ValidationException;
use Lemmon\Validator\Validator;

it('exposes code, message, path, and params on a leaf constraint failure', function () {
    [$valid, , $errors] = Validator::isString()->minLength(5)->tryValidate('hi');

    expect($valid)->toBe(false);
    expect($errors)->toHaveCount(1);

    $error = $errors[0];
    expect($error)->toBeInstanceOf(ValidationError::class);
    expect($error->getCode())->toBe(ValidationCode::STRING_TOO_SHORT);
    expect($error->getMessage())->toBe('Value must be at least 5 characters long');
    expect($error->getPath())->toBe('');
    expect($error->getParams())->toBe(['min' => 5]);
});

it('emits INVALID_TYPE with the expected type in params', function () {
    [, , $errors] = Validator::isInt()->tryValidate('nope');

    expect($errors[0]->getCode())->toBe(ValidationCode::INVALID_TYPE);
    expect($errors[0]->getParams())->toBe(['expected' => 'int']);
});

it('emits REQUIRED for a missing required value', function () {
    [, , $errors] = Validator::isString()->required()->tryValidate(null);

    expect($errors[0]->getCode())->toBe(ValidationCode::REQUIRED);
    expect($errors[0]->getMessage())->toBe('Value is required');
});

it('builds dotted paths through nested schemas via getStructuredErrors', function () {
    $schema = Validator::isAssociative([
        'user' => Validator::isAssociative([
            'address' => Validator::isAssociative([
                'street' => Validator::isString()->required(),
            ]),
        ]),
    ]);

    try {
        $schema->validate(['user' => ['address' => []]]);
        expect(false)->toBe(true); // should not reach
    } catch (ValidationException $e) {
        $errors = $e->getStructuredErrors();
        expect($errors)->toHaveCount(1);
        expect($errors[0]->getPath())->toBe('user.address.street');
        expect($errors[0]->getCode())->toBe(ValidationCode::REQUIRED);
    }
});

it('prefixes array item paths with the index', function () {
    $schema = Validator::isArray()->items(Validator::isInt()->min(1));

    [, , $errors] = $schema->tryValidate([5, -2, 0]);

    $byPath = [];
    foreach ($errors as $error) {
        $byPath[$error->getPath()] = $error->getCode();
    }

    expect($byPath)->toBe([
        '1' => ValidationCode::NUMBER_TOO_SMALL,
        '2' => ValidationCode::NUMBER_TOO_SMALL,
    ]);
});

it('produces field-level paths for uniqueField', function () {
    $schema = Validator::isAssociative([
        'links' => Validator::isArray()
            ->items(Validator::isAssociative([
                'slug' => Validator::isString(),
            ]))
            ->uniqueField('slug'),
    ]);

    try {
        $schema->validate(['links' => [['slug' => 'a'], ['slug' => 'a']]]);
        expect(false)->toBe(true);
    } catch (ValidationException $e) {
        $paths = array_map(static fn(ValidationError $err) => $err->getPath(), $e->getStructuredErrors());
        $codes = array_map(static fn(ValidationError $err) => $err->getCode(), $e->getStructuredErrors());

        expect($paths)->toBe(['links.0.slug', 'links.1.slug']);
        expect($codes)->toBe([ValidationCode::NOT_UNIQUE, ValidationCode::NOT_UNIQUE]);
    }
});

it('lets a custom satisfies() rule supply its own code', function () {
    $validator = Validator::isString()->satisfies(
        static fn($value) => $value === 'ok',
        'Must be ok',
        'MUST_BE_OK',
    );

    [, , $errors] = $validator->tryValidate('no');

    expect($errors[0]->getCode())->toBe('MUST_BE_OK');
    expect($errors[0]->getMessage())->toBe('Must be ok');
});

it('defaults a custom satisfies() rule to the CUSTOM code', function () {
    [, , $errors] = Validator::isString()
        ->satisfies(static fn($value) => false, 'nope')
        ->tryValidate('x');

    expect($errors[0]->getCode())->toBe(ValidationCode::CUSTOM);
});

it('interpolates {name} placeholders in messages from params', function () {
    $validator = Validator::isString()->satisfies(
        static fn($value) => false,
        'Length must be over {min}, got "{value}"',
        'TOO_SHORT',
        ['min' => 8, 'value' => 'hi'],
    );

    [, , $errors] = $validator->tryValidate('hi');

    expect($errors[0]->getMessage())->toBe('Length must be over 8, got "hi"');
    expect($errors[0]->getParams())->toBe(['min' => 8, 'value' => 'hi']);
});

it('serializes params as a JSON object, even when empty', function () {
    $withParams = new ValidationError('user.name', ValidationCode::STRING_TOO_SHORT, 'too short', ['min' => 3]);
    $noParams = new ValidationError('', ValidationCode::REQUIRED, 'Value is required');

    expect(json_encode($withParams))
        ->toBe('{"path":"user.name","code":"STRING_TOO_SHORT","message":"too short","params":{"min":3}}');

    // Empty params must be {} (object), never [] -- keeps the API shape predictable
    expect(json_encode($noParams))
        ->toBe('{"path":"","code":"REQUIRED","message":"Value is required","params":{}}');
});

it('records enum allowed values in params', function () {
    [, , $errors] = Validator::isString()->in(['a', 'b'])->tryValidate('c');

    expect($errors[0]->getCode())->toBe(ValidationCode::IN);
    expect($errors[0]->getParams())->toBe(['allowed' => ['a', 'b']]);
});
