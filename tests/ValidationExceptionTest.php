<?php

declare(strict_types=1);

use Lemmon\Validator\ValidationException;
use Lemmon\Validator\Validator;

it('should flatten root-level scalar validator errors', function () {
    $validator = Validator::isString()
        ->required()
        ->minLength(5)
        ->email();

    try {
        $validator->validate('ab');
    } catch (ValidationException $e) {
        $flattened = $e->getFlattenedErrors();
        // Note: Validation stops at first error, so we get one error
        expect($flattened)->toBe([
            ['path' => '_root', 'message' => 'Value must be at least 5 characters long'],
        ]);
    }
});

it('should flatten simple associative schema errors', function () {
    $schema = Validator::isAssociative([
        'name' => Validator::isString()->required(),
        'email' => Validator::isString()->email()->required(),
        'age' => Validator::isInt()->min(18),
    ]);

    $input = [
        'age' => 16, // Missing 'name' and 'email', invalid 'age'
    ];

    try {
        $schema->validate($input);
    } catch (ValidationException $e) {
        $flattened = $e->getFlattenedErrors();
        expect($flattened)->toBe([
            ['path' => 'name', 'message' => 'Value is required'],
            ['path' => 'email', 'message' => 'Value is required'],
            ['path' => 'age', 'message' => 'Value must be at least 18'],
        ]);
    }
});

it('should flatten field errors', function () {
    $schema = Validator::isAssociative([
        'password' => Validator::isString()
            ->required()
            ->minLength(8)
            ->pattern('/[A-Z]/', 'Must contain uppercase')
            ->pattern('/\d/', 'Must contain number'),
    ]);

    $input = ['password' => 'weak'];

    try {
        $schema->validate($input);
    } catch (ValidationException $e) {
        $flattened = $e->getFlattenedErrors();
        // Note: Validation stops at first error per field
        expect($flattened)->toBe([
            ['path' => 'password', 'message' => 'Value must be at least 8 characters long'],
        ]);
    }
});

it('should flatten deeply nested structure errors', function () {
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

    $input = [
        'user' => [
            'profile' => [
                'email' => 'invalid-email',
                'phone' => '123',
            ],
            'address' => [],
        ],
    ];

    try {
        $schema->validate($input);
    } catch (ValidationException $e) {
        $flattened = $e->getFlattenedErrors();
        expect($flattened)->toBe([
            ['path' => 'user.profile.email', 'message' => 'Value must be a valid email address'],
            ['path' => 'user.profile.phone', 'message' => 'Invalid phone format'],
            ['path' => 'user.address.street', 'message' => 'Value is required'],
        ]);
    }
});

it('should flatten array items validation errors', function () {
    $schema = Validator::isAssociative([
        'items' => Validator::isArray()->items(Validator::isInt()->min(1)),
    ]);

    $input = [
        'items' => [5, -2, 0, 10],
    ];

    try {
        $schema->validate($input);
    } catch (ValidationException $e) {
        $flattened = $e->getFlattenedErrors();
        // Array validation collects all errors with proper indices
        expect($flattened)->toBe([
            ['path' => 'items.1', 'message' => 'Value must be at least 1'],
            ['path' => 'items.2', 'message' => 'Value must be at least 1'],
        ]);
    }
});

it('should flatten nested arrays with object items errors', function () {
    $schema = Validator::isAssociative([
        'users' => Validator::isArray()->items(Validator::isAssociative([
            'name' => Validator::isString()->required(),
            'email' => Validator::isString()->email()->required(),
        ])),
    ]);

    $input = [
        'users' => [
            ['name' => 'John'], // Missing email
        ],
    ];

    try {
        $schema->validate($input);
    } catch (ValidationException $e) {
        $flattened = $e->getFlattenedErrors();
        // Array item errors preserve the index in the structure
        // The error is stored as ['users' => ['0' => ['email' => ['error']]]]
        // So the flattened path is 'users.0.email'
        expect($flattened)->toBe([
            ['path' => 'users.0.email', 'message' => 'Value is required'],
        ]);
    }
});

it('should flatten root-level container type errors', function () {
    $schema = Validator::isAssociative([
        'name' => Validator::isString()->required(),
    ]);

    try {
        $schema->validate('not-an-array');
    } catch (ValidationException $e) {
        $flattened = $e->getFlattenedErrors();
        expect($flattened)->toBe([
            ['path' => '_root', 'message' => 'Input must be an associative array'],
        ]);
    }
});

it('should flatten mixed scenarios with multiple error types', function () {
    $schema = Validator::isAssociative([
        'title' => Validator::isString()->required()->minLength(3),
        'tags' => Validator::isArray()->items(Validator::isString()->minLength(2)),
        'author' => Validator::isAssociative([
            'name' => Validator::isString()->required(),
            'contact' => Validator::isAssociative([
                'email' => Validator::isString()->email(),
            ]),
        ]),
    ]);

    $input = [
        'title' => 'Hi', // Too short
        'tags' => ['a'], // Invalid item
        'author' => [
            'contact' => [
                'email' => 'invalid',
            ],
        ],
    ];

    try {
        $schema->validate($input);
    } catch (ValidationException $e) {
        $flattened = $e->getFlattenedErrors();
        expect($flattened)->toBeArray();
        expect($flattened)->not->toBeEmpty();

        // Check that we have errors for different paths
        $paths = array_column($flattened, 'path');
        expect($paths)->toContain('title');
        expect($paths)->toContain('author.name');
        expect($paths)->toContain('author.contact.email');
        // Array item errors preserve the index, so path is 'tags.0'
        expect($paths)->toContain('tags.0');
    }
});

it('should handle empty error array', function () {
    $exception = new ValidationException([]);
    $flattened = $exception->getFlattenedErrors();
    expect($flattened)->toBe([]);
});

it('should handle single root-level error', function () {
    $exception = new ValidationException(['Value must be a string']);
    $flattened = $exception->getFlattenedErrors();
    expect($flattened)->toBe([
        ['path' => '_root', 'message' => 'Value must be a string'],
    ]);
});

it('should handle object validator errors', function () {
    $schema = Validator::isObject([
        'name' => Validator::isString()->required(),
        'age' => Validator::isInt()->min(18),
    ]);

    $input = (object) [
        'age' => 16,
    ];

    try {
        $schema->validate($input);
    } catch (ValidationException $e) {
        $flattened = $e->getFlattenedErrors();
        expect($flattened)->toBe([
            ['path' => 'name', 'message' => 'Value is required'],
            ['path' => 'age', 'message' => 'Value must be at least 18'],
        ]);
    }
});

it('should handle root-level object validator type error', function () {
    $schema = Validator::isObject([
        'name' => Validator::isString()->required(),
    ]);

    try {
        $schema->validate('not-an-object');
    } catch (ValidationException $e) {
        $flattened = $e->getFlattenedErrors();
        expect($flattened)->toBe([
            ['path' => '_root', 'message' => 'Input must be an object'],
        ]);
    }
});

it('should flatten errors from tryValidate using static method', function () {
    $schema = Validator::isAssociative([
        'name' => Validator::isString()->required(),
        'email' => Validator::isString()->email()->required(),
        'age' => Validator::isInt()->min(18),
    ]);

    [$valid, $data, $errors] = $schema->tryValidate(['age' => 16]);

    expect($valid)->toBe(false);
    expect($errors)->not->toBeNull();

    $flattened = ValidationException::flattenErrors($errors);
    expect($flattened)->toBe([
        ['path' => 'name', 'message' => 'Value is required'],
        ['path' => 'email', 'message' => 'Value is required'],
        ['path' => 'age', 'message' => 'Value must be at least 18'],
    ]);
});

it('should return empty array when flattening null errors', function () {
    $flattened = ValidationException::flattenErrors(null);
    expect($flattened)->toBe([]);
});

it('should flatten scalar validator errors from tryValidate', function () {
    $validator = Validator::isString()
        ->required()
        ->minLength(5)
        ->email();

    [$valid, $data, $errors] = $validator->tryValidate('ab');

    expect($valid)->toBe(false);
    expect($errors)->not->toBeNull();

    $flattened = ValidationException::flattenErrors($errors);
    expect($flattened)->toBe([
        ['path' => '_root', 'message' => 'Value must be at least 5 characters long'],
    ]);
});
