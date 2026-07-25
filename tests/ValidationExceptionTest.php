<?php

declare(strict_types=1);

use Lemmon\Validator\ValidationCode;
use Lemmon\Validator\ValidationError;
use Lemmon\Validator\ValidationException;
use Lemmon\Validator\Validator;

it('returns all structured errors keyed by dotted path', function () {
    $schema = Validator::isAssociative([
        'name' => Validator::isString()->required(),
        'email' => Validator::isString()->email()->required(),
        'age' => Validator::isInt()->min(18),
    ]);

    try {
        $schema->validate(['age' => 16]);
        expect(false)->toBe(true);
    } catch (ValidationException $e) {
        $byPath = [];
        foreach ($e->getErrors() as $err) {
            $byPath[$err->getPath()] = $err->getCode();
        }
        expect($byPath)->toBe([
            'name' => ValidationCode::REQUIRED,
            'email' => ValidationCode::REQUIRED,
            'age' => ValidationCode::NUMBER_TOO_SMALL,
        ]);
    }
});

it('reports a scalar validator failure as a root error (empty path)', function () {
    try {
        Validator::isString()->email()->validate('invalid');
        expect(false)->toBe(true);
    } catch (ValidationException $e) {
        $errors = $e->getErrors();
        expect($errors)->toHaveCount(1);
        expect($errors[0]->getPath())->toBe('');
        expect($errors[0]->getCode())->toBe(ValidationCode::EMAIL);
    }
});

it('reports a container type error at the root path', function () {
    $schema = Validator::isAssociative(['name' => Validator::isString()->required()]);

    try {
        $schema->validate('not-an-array');
        expect(false)->toBe(true);
    } catch (ValidationException $e) {
        expect($e->getErrors()[0]->getPath())->toBe('');
        expect($e->getErrors()[0]->getCode())->toBe(ValidationCode::INVALID_TYPE);
    }
});

it('builds full dotted paths through deeply nested schemas', function () {
    $schema = Validator::isAssociative([
        'user' => Validator::isAssociative([
            'profile' => Validator::isAssociative([
                'email' => Validator::isString()->email()->required(),
                'phone' => Validator::isString()->pattern('/^\d{10}$/', 'Invalid phone format'),
            ]),
            'address' => Validator::isAssociative([
                'street' => Validator::isString()->required(),
            ]),
        ]),
    ]);

    try {
        $schema->validate([
            'user' => [
                'profile' => ['email' => 'invalid-email', 'phone' => '123'],
                'address' => [],
            ],
        ]);
        expect(false)->toBe(true);
    } catch (ValidationException $e) {
        $paths = array_map(fn($err) => $err->getPath(), $e->getErrors());
        expect($paths)->toBe([
            'user.profile.email',
            'user.profile.phone',
            'user.address.street',
        ]);
    }
});

it('preserves array indices in error paths', function () {
    $schema = Validator::isAssociative([
        'items' => Validator::isArray()->items(Validator::isInt()->min(1)),
    ]);

    try {
        $schema->validate(['items' => [5, -2, 0, 10]]);
        expect(false)->toBe(true);
    } catch (ValidationException $e) {
        $paths = array_map(fn($err) => $err->getPath(), $e->getErrors());
        expect($paths)->toBe(['items.1', 'items.2']);
    }
});

it('produces dotted paths for object validator schemas', function () {
    $schema = Validator::isObject([
        'name' => Validator::isString()->required(),
        'age' => Validator::isInt()->min(18),
    ]);

    try {
        $schema->validate((object) ['age' => 16]);
        expect(false)->toBe(true);
    } catch (ValidationException $e) {
        $paths = array_map(fn($err) => $err->getPath(), $e->getErrors());
        expect($paths)->toBe(['name', 'age']);
    }
});

it('filters to a field and its subtree via getErrors($path)', function () {
    $schema = Validator::isAssociative([
        'name' => Validator::isString()->required(),
        'address' => Validator::isAssociative([
            'street' => Validator::isString()->required(),
            'zip' => Validator::isString()->required(),
        ]),
    ]);

    try {
        $schema->validate(['address' => []]);
        expect(false)->toBe(true);
    } catch (ValidationException $e) {
        // Whole subtree
        $addressPaths = array_map(fn($err) => $err->getPath(), $e->getErrors('address'));
        expect($addressPaths)->toBe(['address.street', 'address.zip']);

        // A single leaf
        $street = $e->getErrors('address.street');
        expect($street)->toHaveCount(1);
        expect($street[0]->getCode())->toBe(ValidationCode::REQUIRED);

        // A sibling scalar field
        $name = $e->getErrors('name');
        expect($name)->toHaveCount(1);
        expect($name[0]->getPath())->toBe('name');
    }
});

it('filters by exact path segments when keys contain dots', function () {
    $literal = new ValidationError(['user.name', 'email'], 'LITERAL', 'Literal dotted key');
    $nested = new ValidationError(['user', 'name', 'email'], 'NESTED', 'Nested key');
    $exception = new ValidationException([$literal, $nested]);

    expect($exception->getErrors(['user.name']))->toBe([$literal]);
    expect($exception->getErrors(['user']))->toBe([$nested]);
    expect($exception->getErrors([]))->toBe([]);
});

it('distinguishes integer indices from numeric string keys in segment filters', function () {
    $index = new ValidationError(['items', 0], 'INDEX', 'Integer index');
    $key = new ValidationError(['items', '0'], 'KEY', 'String key');
    $exception = new ValidationException([$index, $key]);

    expect($exception->getErrors(['items', 0]))->toBe([$index]);
    expect($exception->getErrors(['items', '0']))->toBe([$key]);
});

it('does not match sibling fields that share a path prefix', function () {
    $schema = Validator::isAssociative([
        'name' => Validator::isString()->required(),
        'name_full' => Validator::isString()->required(),
    ]);

    try {
        $schema->validate([]);
        expect(false)->toBe(true);
    } catch (ValidationException $e) {
        $paths = array_map(fn($err) => $err->getPath(), $e->getErrors('name'));
        expect($paths)->toBe(['name']); // not 'name_full'
    }
});

it("returns only root-level errors for getErrors('')", function () {
    try {
        Validator::isString()->email()->validate('nope');
        expect(false)->toBe(true);
    } catch (ValidationException $e) {
        $root = $e->getErrors('');
        expect($root)->toHaveCount(1);
        expect($root[0]->getPath())->toBe('');
    }
});

it("getErrors('') matches only the exact root, not an empty-key path", function () {
    // The dotted view of an empty schema-key segment starts with a dot ('.hidden'), while the exact
    // segments preserve ['', 'hidden']; neither representation is a root-level error.
    $schema = Validator::isAssociative([
        '' => Validator::isAssociative(['hidden' => Validator::isString()->required()]),
        'name' => Validator::isString()->required(),
    ]);

    try {
        $schema->validate(['' => []]);
        expect(false)->toBe(true);
    } catch (ValidationException $e) {
        $allPaths = array_map(fn($err) => $err->getPath(), $e->getErrors());
        expect($allPaths)->toContain('.hidden');

        expect($e->getErrors(''))->toBe([]);
        expect($e->getErrors([]))->toBe([]);
        expect($e->getErrors(['']))->toHaveCount(1);
    }
});

it('returns an empty list when no error matches the path', function () {
    $schema = Validator::isAssociative(['name' => Validator::isString()->required()]);

    try {
        $schema->validate([]);
        expect(false)->toBe(true);
    } catch (ValidationException $e) {
        expect($e->getErrors('email'))->toBe([]);
    }
});

it('encodes the structured errors as the exception message', function () {
    try {
        Validator::isString()->minLength(5)->validate('hi');
        expect(false)->toBe(true);
    } catch (ValidationException $e) {
        $decoded = json_decode($e->getMessage(), true);
        expect($decoded)->toBe([
            [
                'path' => '',
                'code' => 'STRING_TOO_SHORT',
                'message' => 'Value must be at least 5 characters long',
                'params' => ['min' => 5],
            ],
        ]);
    }
});

it('handles an empty error list', function () {
    $exception = new ValidationException([]);
    expect($exception->getErrors())->toBe([]);
    expect($exception->getErrors('anything'))->toBe([]);
});
