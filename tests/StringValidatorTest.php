<?php

use Lemmon\Validator;

it('should validate email strings', function () {
    $validator = Validator::isString()->email();

    // Valid email
    $data = $validator->validate('test@example.com');
    expect($data)->toBe('test@example.com');

    // Invalid email
    $validator->validate('not-an-email');
})->throws(Lemmon\ValidationException::class, 'Value must be a valid email address');

it('should reject non-string values for email validation', function () {
    $validator = Validator::isString()->email();
    $validator->validate(123);
})->throws(Lemmon\ValidationException::class, 'Value must be a string');

it('should handle required and optional email fields', function () {
    // Optional: null should pass
    $optionalValidator = Validator::isString()->email();
    $data = $optionalValidator->validate(null);
    expect($data)->toBeNull();

    // Required: null should fail
    $requiredValidator = Validator::isString()->email()->required();
    $requiredValidator->validate(null);
})->throws(Lemmon\ValidationException::class, 'Value is required');

it('should use custom error message for email validation', function () {
    $validator = Validator::isString()->email('Please provide a valid email');
    $validator->validate('invalid-email');
})->throws(Lemmon\ValidationException::class, 'Please provide a valid email');

it('should validate URL strings', function () {
    $validator = Validator::isString()->url();

    expect($validator->validate('https://example.com'))->toBe('https://example.com');
    expect($validator->validate('http://test.org'))->toBe('http://test.org');
    expect($validator->validate('ftp://files.example.com'))->toBe('ftp://files.example.com');

    $validator->validate('not-a-url');
})->throws(Lemmon\ValidationException::class, 'Value must be a valid URL');

it('should validate UUID strings', function () {
    $validator = Validator::isString()->uuid();

    expect($validator->validate('550e8400-e29b-41d4-a716-446655440000'))->toBe('550e8400-e29b-41d4-a716-446655440000');
    expect($validator->validate('6ba7b810-9dad-11d1-80b4-00c04fd430c8'))->toBe('6ba7b810-9dad-11d1-80b4-00c04fd430c8');

    $validator->validate('not-a-uuid');
})->throws(Lemmon\ValidationException::class, 'Value must be a valid UUID');

it('should validate IP addresses', function () {
    $validator = Validator::isString()->ip();

    expect($validator->validate('192.168.1.1'))->toBe('192.168.1.1');
    expect($validator->validate('2001:0db8:85a3:0000:0000:8a2e:0370:7334'))->toBe('2001:0db8:85a3:0000:0000:8a2e:0370:7334');

    $validator->validate('not-an-ip');
})->throws(Lemmon\ValidationException::class, 'Value must be a valid IP address');

it('should validate string length constraints', function () {
    $minValidator = Validator::isString()->minLength(3);
    expect($minValidator->validate('hello'))->toBe('hello');
    $minValidator->validate('hi');
})->throws(Lemmon\ValidationException::class, 'Value must be at least 3 characters long');

it('should validate max length constraints', function () {
    $maxValidator = Validator::isString()->maxLength(5);
    expect($maxValidator->validate('hello'))->toBe('hello');
    $maxValidator->validate('too long');
})->throws(Lemmon\ValidationException::class, 'Value must be at most 5 characters long');

it('should validate exact length constraints', function () {
    $exactValidator = Validator::isString()->length(5);
    expect($exactValidator->validate('hello'))->toBe('hello');
    $exactValidator->validate('hi');
})->throws(Lemmon\ValidationException::class, 'Value must be exactly 5 characters long');

it('should validate regex patterns', function () {
    $phoneValidator = Validator::isString()->pattern('/^\d{3}-\d{3}-\d{4}$/');
    expect($phoneValidator->validate('123-456-7890'))->toBe('123-456-7890');
    $phoneValidator->validate('invalid-phone');
})->throws(Lemmon\ValidationException::class, 'Value does not match the required pattern');

it('should validate datetime formats', function () {
    $datetimeValidator = Validator::isString()->datetime();
    expect($datetimeValidator->validate('2023-12-25T10:30:00'))->toBe('2023-12-25T10:30:00');
    $datetimeValidator->validate('invalid-datetime');
})->throws(Lemmon\ValidationException::class);

it('should validate date formats', function () {
    $dateValidator = Validator::isString()->date();
    expect($dateValidator->validate('2023-12-25'))->toBe('2023-12-25');
    $dateValidator->validate('invalid-date');
})->throws(Lemmon\ValidationException::class, "Value must be a valid date in format 'Y-m-d'");

it('should nullify empty string when nullifyEmpty is called', function () {
    $stringValidator = Validator::isString()->nullifyEmpty();
    expect($stringValidator->validate(''))->toBe(null);

    // Should not nullify non-empty values
    expect($stringValidator->validate('hello'))->toBe('hello');
    expect($stringValidator->validate(null))->toBe(null);
});
