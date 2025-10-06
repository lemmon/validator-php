<?php

use Lemmon\Validator;
use Lemmon\ValidationException;

describe('Validator Static Logical Combinators', function () {

    describe('anyOf', function () {
        it('should validate when any validator passes', function () {
            $validator = Validator::anyOf([
                Validator::isString()->email(),
                Validator::isInt()->positive(),
                Validator::isString()->uuid()
            ]);

            // Test with email (first validator)
            expect($validator->validate('test@example.com'))->toBe('test@example.com');

            // Test with positive integer (second validator)
            expect($validator->validate(123))->toBe(123);

            // Test with UUID (third validator)
            expect($validator->validate('550e8400-e29b-41d4-a716-446655440000'))->toBe('550e8400-e29b-41d4-a716-446655440000');
        });

        it('should fail when no validator passes', function () {
            $validator = Validator::anyOf([
                Validator::isString()->email(),
                Validator::isInt()->positive()
            ]);

            expect(fn () => $validator->validate('not-email-or-positive-int'))
                ->toThrow(ValidationException::class);
        });

        it('should work with mixed types in array validation', function () {
            $arrayValidator = Validator::isArray()->items(
                Validator::anyOf([
                    Validator::isString(),
                    Validator::isInt(),
                    Validator::isFloat()
                ])
            );

            $result = $arrayValidator->validate(['hello', 42, 3.14]);
            expect($result)->toBe(['hello', 42, 3.14]);
        });

        it('should use custom error message', function () {
            $validator = Validator::anyOf([
                Validator::isString()->email(),
                Validator::isInt()->positive()
            ], 'Must be either a valid email or positive integer');

            expect(fn () => $validator->validate('invalid'))
                ->toThrow(ValidationException::class, 'Must be either a valid email or positive integer');
        });

        it('should work with tryValidate', function () {
            $validator = Validator::anyOf([
                Validator::isString()->email(),
                Validator::isInt()->positive()
            ]);

            [$valid, $data, $errors] = $validator->tryValidate('test@example.com');
            expect($valid)->toBe(true);
            expect($data)->toBe('test@example.com');
            expect($errors)->toBe(null);

            [$valid, $data, $errors] = $validator->tryValidate('invalid');
            expect($valid)->toBe(false);
            expect($data)->toBe('invalid');
            expect($errors)->toContain('Value must satisfy at least one validation rule');
        });
    });

    describe('allOf', function () {
        it('should validate when all validators pass', function () {
            $validator = Validator::allOf([
                Validator::isString()->minLength(3),
                Validator::isString()->maxLength(10),
                Validator::isString()->pattern('/^[a-z]+$/')
            ]);

            expect($validator->validate('hello'))->toBe('hello');
            expect($validator->validate('world'))->toBe('world');
        });

        it('should fail when any validator fails', function () {
            $validator = Validator::allOf([
                Validator::isString()->minLength(5),
                Validator::isString()->maxLength(10),
                Validator::isString()->pattern('/^[a-z]+$/')
            ]);

            // Fails minLength
            expect(fn () => $validator->validate('hi'))
                ->toThrow(ValidationException::class);

            // Fails pattern
            expect(fn () => $validator->validate('Hello'))
                ->toThrow(ValidationException::class);
        });

        it('should work with different validator types', function () {
            // This tests that allOf can work with any type that passes all validators
            $validator = Validator::allOf([
                Validator::isString(),
                Validator::isString()->minLength(1)
            ]);

            expect($validator->validate('test'))->toBe('test');
        });

        it('should use custom error message', function () {
            $validator = Validator::allOf([
                Validator::isString()->minLength(5),
                Validator::isString()->pattern('/^[a-z]+$/')
            ], 'Must be lowercase string with at least 5 characters');

            expect(fn () => $validator->validate('Hi'))
                ->toThrow(ValidationException::class, 'Must be lowercase string with at least 5 characters');
        });

        it('should work with tryValidate', function () {
            $validator = Validator::allOf([
                Validator::isString()->minLength(3),
                Validator::isString()->maxLength(10)
            ]);

            [$valid, $data, $errors] = $validator->tryValidate('hello');
            expect($valid)->toBe(true);
            expect($data)->toBe('hello');
            expect($errors)->toBe(null);

            [$valid, $data, $errors] = $validator->tryValidate('hi');
            expect($valid)->toBe(false);
            expect($data)->toBe('hi');
            expect($errors)->toContain('Value must satisfy all validation rules');
        });
    });

    describe('not', function () {
        it('should validate when the validator fails', function () {
            $validator = Validator::not(Validator::isString()->email());

            expect($validator->validate('not-an-email'))->toBe('not-an-email');
            expect($validator->validate('hello world'))->toBe('hello world');
            expect($validator->validate(123))->toBe(123);
        });

        it('should fail when the validator passes', function () {
            $validator = Validator::not(Validator::isString()->email());

            expect(fn () => $validator->validate('test@example.com'))
                ->toThrow(ValidationException::class);
        });

        it('should work with complex validators', function () {
            $validator = Validator::not(
                Validator::isString()->oneOf(['banned', 'suspended'])
            );

            expect($validator->validate('active'))->toBe('active');
            expect($validator->validate('pending'))->toBe('pending');

            expect(fn () => $validator->validate('banned'))
                ->toThrow(ValidationException::class);
        });

        it('should use custom error message', function () {
            $validator = Validator::not(
                Validator::isString()->oneOf(['admin', 'root']),
                'Username cannot be admin or root'
            );

            expect(fn () => $validator->validate('admin'))
                ->toThrow(ValidationException::class, 'Username cannot be admin or root');
        });

        it('should work with tryValidate', function () {
            $validator = Validator::not(Validator::isString()->email());

            [$valid, $data, $errors] = $validator->tryValidate('not-email');
            expect($valid)->toBe(true);
            expect($data)->toBe('not-email');
            expect($errors)->toBe(null);

            [$valid, $data, $errors] = $validator->tryValidate('test@example.com');
            expect($valid)->toBe(false);
            expect($data)->toBe('test@example.com');
            expect($errors)->toContain('Value must not satisfy the validation rule');
        });
    });

    describe('complex combinations', function () {
        it('should work with nested logical combinators', function () {
            $validator = Validator::anyOf([
                Validator::allOf([
                    Validator::isString(),
                    Validator::isString()->email()
                ]),
                Validator::allOf([
                    Validator::isInt(),
                    Validator::isInt()->positive()
                ])
            ]);

            expect($validator->validate('test@example.com'))->toBe('test@example.com');
            expect($validator->validate(123))->toBe(123);

            expect(fn () => $validator->validate('not-email'))
                ->toThrow(ValidationException::class);
            expect(fn () => $validator->validate(-5))
                ->toThrow(ValidationException::class);
        });

        it('should work in schema validation', function () {
            $schema = Validator::isAssociative([
                'id' => Validator::anyOf([
                    Validator::isInt()->positive(),
                    Validator::isString()->uuid()
                ]),
                'name' => Validator::allOf([
                    Validator::isString()->required(),
                    Validator::isString()->minLength(2),
                    Validator::isString()->maxLength(50)
                ]),
                'status' => Validator::not(
                    Validator::isString()->oneOf(['banned', 'suspended']),
                    'User cannot have banned or suspended status'
                )
            ]);

            $validData = [
                'id' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
                'name' => 'John Doe',
                'status' => 'active'
            ];

            $result = $schema->validate($validData);
            expect($result['id'])->toBe('f47ac10b-58cc-4372-a567-0e02b2c3d479');
            expect($result['name'])->toBe('John Doe');
            expect($result['status'])->toBe('active');

            // Test with integer ID
            $validData2 = [
                'id' => 123,
                'name' => 'Jane Doe',
                'status' => 'pending'
            ];

            $result2 = $schema->validate($validData2);
            expect($result2['id'])->toBe(123);
        });

        it('should handle edge cases with null and default values', function () {
            $validator = Validator::anyOf([
                Validator::isString()->email(),
                Validator::isInt()->positive()
            ])->default('default@example.com');

            expect($validator->validate(null))->toBe('default@example.com');
            expect($validator->validate('test@example.com'))->toBe('test@example.com');
            expect($validator->validate(123))->toBe(123);
        });

        it('should work with required fields', function () {
            $validator = Validator::anyOf([
                Validator::isString()->email(),
                Validator::isInt()->positive()
            ])->required();

            expect(fn () => $validator->validate(null))
                ->toThrow(ValidationException::class);

            expect($validator->validate('test@example.com'))->toBe('test@example.com');
        });
    });
});
