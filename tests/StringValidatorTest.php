<?php

declare(strict_types=1);

use Lemmon\Validator\Base64Variant;
use Lemmon\Validator\IpVersion;
use Lemmon\Validator\UuidVariant;
use Lemmon\Validator\ValidationException;
use Lemmon\Validator\Validator;

it('should validate email strings', function () {
    $validator = Validator::isString()->email();

    // Valid email
    $data = $validator->validate('test@example.com');
    expect($data)->toBe('test@example.com');

    // Invalid email
    $validator->validate('not-an-email');
})->throws(ValidationException::class, 'Value must be a valid email address');

it('should reject non-string values for email validation', function () {
    $validator = Validator::isString()->email();
    $validator->validate(123);
})->throws(ValidationException::class, 'Value must be a string');

it('should handle required and optional email fields', function () {
    // Optional: null should pass
    $optionalValidator = Validator::isString()->email();
    $data = $optionalValidator->validate(null);
    expect($data)->toBeNull();

    // Required: null should fail (order independent with smart null handling)
    $requiredValidator = Validator::isString()->email()->required();
    $requiredValidator->validate(null);
})->throws(ValidationException::class, 'Value is required');

it('should use custom error message for email validation', function () {
    $validator = Validator::isString()->email('Please provide a valid email');
    $validator->validate('invalid-email');
})->throws(ValidationException::class, 'Please provide a valid email');

it('should validate URL strings', function () {
    $validator = Validator::isString()->url();

    expect($validator->validate('https://example.com'))->toBe('https://example.com');
    expect($validator->validate('http://test.org'))->toBe('http://test.org');
    expect($validator->validate('ftp://files.example.com'))->toBe('ftp://files.example.com');

    $validator->validate('not-a-url');
})->throws(ValidationException::class, 'Value must be a valid URL');

it('should validate UUID strings with UuidVariant enum', function () {
    // Any version (default)
    $anyValidator = Validator::isString()->uuid();
    expect($anyValidator->validate('550e8400-e29b-41d4-a716-446655440000'))
        ->toBe('550e8400-e29b-41d4-a716-446655440000'); // V4
    expect($anyValidator->validate('6ba7b810-9dad-11d1-80b4-00c04fd430c8'))
        ->toBe('6ba7b810-9dad-11d1-80b4-00c04fd430c8'); // V1

    $anyValidator->validate('not-a-uuid');
})->throws(ValidationException::class, 'Value must be a valid UUID');

it('should validate UUID version 1', function () {
    $validator = Validator::isString()->uuid(UuidVariant::V1);

    // Valid UUID v1
    expect($validator->validate('6ba7b810-9dad-11d1-80b4-00c04fd430c8'))
        ->toBe('6ba7b810-9dad-11d1-80b4-00c04fd430c8');

    // Invalid: wrong version
    $validator->validate('550e8400-e29b-41d4-a716-446655440000'); // V4
})->throws(ValidationException::class, 'Value must be a valid UUID version 1');

it('should validate IP addresses', function () {
    $validator = Validator::isString()->ip();

    expect($validator->validate('192.168.1.1'))->toBe('192.168.1.1');
    expect($validator->validate('2001:0db8:85a3:0000:0000:8a2e:0370:7334'))
        ->toBe('2001:0db8:85a3:0000:0000:8a2e:0370:7334');

    $validator->validate('not-an-ip');
})->throws(ValidationException::class, 'Value must be a valid IP address');

it('should validate string length constraints', function () {
    $minValidator = Validator::isString()->minLength(3);
    expect($minValidator->validate('hello'))->toBe('hello');
    $minValidator->validate('hi');
})->throws(ValidationException::class, 'Value must be at least 3 characters long');

it('should validate max length constraints', function () {
    $maxValidator = Validator::isString()->maxLength(5);
    expect($maxValidator->validate('hello'))->toBe('hello');
    $maxValidator->validate('too long');
})->throws(ValidationException::class, 'Value must be at most 5 characters long');

it('should validate exact length constraints', function () {
    $exactValidator = Validator::isString()->length(5);
    expect($exactValidator->validate('hello'))->toBe('hello');
    $exactValidator->validate('hi');
})->throws(ValidationException::class, 'Value must be exactly 5 characters long');

it('should validate string length between bounds', function () {
    $validator = Validator::isString()->between(2, 4);

    expect($validator->validate('hey'))->toBe('hey');

    $validator->validate('h');
})->throws(ValidationException::class, 'Value must be between 2 and 4 characters long');

it('should reject strings longer than the between range', function () {
    $validator = Validator::isString()->between(2, 4);
    $validator->validate('hello');
})->throws(ValidationException::class, 'Value must be between 2 and 4 characters long');

it('should use custom error message for between length validation', function () {
    $validator = Validator::isString()->between(2, 4, 'Length out of range');
    $validator->validate('');
})->throws(ValidationException::class, 'Length out of range');

it('should validate non-empty strings', function () {
    $validator = Validator::isString()->notEmpty();

    expect($validator->validate('hello'))->toBe('hello');

    $validator->validate('');
})->throws(ValidationException::class, 'Value must not be empty');

it('should use custom error message for notEmpty string validation', function () {
    $validator = Validator::isString()->notEmpty('String cannot be empty');
    $validator->validate('');
})->throws(ValidationException::class, 'String cannot be empty');

it('should validate regex patterns', function () {
    $phoneValidator = Validator::isString()->pattern('/^\d{3}-\d{3}-\d{4}$/');
    expect($phoneValidator->validate('123-456-7890'))->toBe('123-456-7890');
    $phoneValidator->validate('invalid-phone');
})->throws(ValidationException::class, 'Value does not match the required pattern');

it('should validate datetime formats', function () {
    $datetimeValidator = Validator::isString()->datetime();
    expect($datetimeValidator->validate('2023-12-25T10:30:00'))->toBe('2023-12-25T10:30:00');
    $datetimeValidator->validate('invalid-datetime');
})->throws(ValidationException::class);

it('should validate date formats', function () {
    $dateValidator = Validator::isString()->date();
    expect($dateValidator->validate('2023-12-25'))->toBe('2023-12-25');
    $dateValidator->validate('invalid-date');
})->throws(ValidationException::class, "Value must be a valid date in format 'Y-m-d'");

it('should nullify empty string when nullifyEmpty is called', function () {
    $stringValidator = Validator::isString()->nullifyEmpty();
    expect($stringValidator->validate(''))->toBe(null);

    // Should not nullify non-empty values
    expect($stringValidator->validate('hello'))->toBe('hello');
    expect($stringValidator->validate(null))->toBe(null);
});

it('should validate hostname strings', function () {
    $validator = Validator::isString()->hostname();

    // Valid hostnames
    expect($validator->validate('example.com'))->toBe('example.com');
    expect($validator->validate('subdomain.example.com'))->toBe('subdomain.example.com');
    expect($validator->validate('test-domain.co.uk'))->toBe('test-domain.co.uk');
    expect($validator->validate('localhost'))->toBe('localhost');

    // Invalid hostnames
    $validator->validate('not a hostname');
})->throws(ValidationException::class, 'Value must be a valid hostname');

it('should use custom error message for hostname validation', function () {
    $validator = Validator::isString()->hostname('Please provide a valid domain name');
    $validator->validate('invalid hostname');
})->throws(ValidationException::class, 'Please provide a valid domain name');

it('should validate time strings', function () {
    $validator = Validator::isString()->time();

    // Valid times (HH:MM format)
    expect($validator->validate('00:00'))->toBe('00:00');
    expect($validator->validate('12:30'))->toBe('12:30');
    expect($validator->validate('23:59'))->toBe('23:59');

    // Valid times (HH:MM:SS format)
    expect($validator->validate('00:00:00'))->toBe('00:00:00');
    expect($validator->validate('12:30:45'))->toBe('12:30:45');
    expect($validator->validate('23:59:59'))->toBe('23:59:59');

    // Invalid times
    $validator->validate('24:00');
})->throws(ValidationException::class, 'Value must be a valid time in format HH:MM or HH:MM:SS');

it('should reject invalid time formats', function () {
    $validator = Validator::isString()->time();
    $validator->validate('25:00');
})->throws(ValidationException::class);

it('should reject time with invalid minutes', function () {
    $validator = Validator::isString()->time();
    $validator->validate('12:60');
})->throws(ValidationException::class);

it('should reject time with invalid seconds', function () {
    $validator = Validator::isString()->time();
    $validator->validate('12:30:60');
})->throws(ValidationException::class);

it('should use custom error message for time validation', function () {
    $validator = Validator::isString()->time('Please enter a valid time');
    $validator->validate('invalid-time');
})->throws(ValidationException::class, 'Please enter a valid time');

it('should validate base64 strings with Base64Variant enum', function () {
    // Standard Base64 (default)
    $standardValidator = Validator::isString()->base64();
    expect($standardValidator->validate('SGVsbG8gV29ybGQ='))->toBe('SGVsbG8gV29ybGQ='); // "Hello World"
    expect($standardValidator->validate('dGVzdA=='))->toBe('dGVzdA=='); // "test"
    expect($standardValidator->validate('YWJj'))->toBe('YWJj'); // "abc" (no padding)
    expect($standardValidator->validate(''))->toBe(''); // Empty string is valid base64

    // Invalid base64 strings
    $standardValidator->validate('SGVsbG8gV29ybGQ!');
})->throws(ValidationException::class, 'Value must be a valid Base64 encoded string');

it('should validate URL-safe base64 strings', function () {
    $urlSafeValidator = Validator::isString()->base64(Base64Variant::UrlSafe);

    // Valid URL-safe base64 strings (no padding, uses - and _)
    expect($urlSafeValidator->validate('SGVsbG8gV29ybGQ'))->toBe('SGVsbG8gV29ybGQ'); // "Hello World" (no padding)
    expect($urlSafeValidator->validate('dGVzdA'))->toBe('dGVzdA'); // "test" (no padding)
    expect($urlSafeValidator->validate('YWJj'))->toBe('YWJj'); // "abc"

    // URL-safe parsers accept both standard and URL-safe variants
    // So standard Base64 with + and / should also be accepted
    expect($urlSafeValidator->validate('SGVsbG8gV29ybGQ='))->toBe('SGVsbG8gV29ybGQ='); // Standard Base64 accepted

    // Invalid: contains invalid characters
    $urlSafeValidator->validate('SGVsbG8gV29ybGQ!');
})->throws(ValidationException::class, 'Value must be a valid URL-safe Base64 encoded string');

it('should validate base64 with Any variant', function () {
    $anyValidator = Validator::isString()->base64(Base64Variant::Any);

    // Accepts standard Base64
    expect($anyValidator->validate('SGVsbG8gV29ybGQ='))->toBe('SGVsbG8gV29ybGQ=');

    // Accepts URL-safe Base64
    expect($anyValidator->validate('SGVsbG8gV29ybGQ'))->toBe('SGVsbG8gV29ybGQ');
});

it('should reject base64 strings with invalid padding', function () {
    $validator = Validator::isString()->base64();
    $validator->validate('SGVsbG8gV29ybGQ===');
})->throws(ValidationException::class);

it('should reject base64 strings with invalid characters', function () {
    $validator = Validator::isString()->base64();
    $validator->validate('SGVsbG8gV29ybGQ@');
})->throws(ValidationException::class);

it('should use custom error message for base64 validation', function () {
    $validator = Validator::isString()->base64(Base64Variant::Standard, 'Must be valid Base64');
    $validator->validate('invalid-base64!');
})->throws(ValidationException::class, 'Must be valid Base64');

it('should validate hex strings', function () {
    $validator = Validator::isString()->hex();

    // Valid hex strings
    expect($validator->validate('deadbeef'))->toBe('deadbeef');
    expect($validator->validate('DEADBEEF'))->toBe('DEADBEEF');
    expect($validator->validate('1234567890abcdef'))->toBe('1234567890abcdef');
    expect($validator->validate('0'))->toBe('0');
    expect($validator->validate('a'))->toBe('a');

    // Invalid hex strings
    $validator->validate('not-hex');
})->throws(ValidationException::class, 'Value must be a valid hexadecimal string');

it('should reject empty hex string', function () {
    $validator = Validator::isString()->hex();
    $validator->validate('');
})->throws(ValidationException::class);

it('should reject hex strings with invalid characters', function () {
    $validator = Validator::isString()->hex();
    $validator->validate('deadbeefg');
})->throws(ValidationException::class);

it('should use custom error message for hex validation', function () {
    $validator = Validator::isString()->hex('Must be hexadecimal');
    $validator->validate('not-hex');
})->throws(ValidationException::class, 'Must be hexadecimal');

it('should provide regex alias for pattern method', function () {
    $patternValidator = Validator::isString()->pattern('/^\d{3}$/');
    $regexValidator = Validator::isString()->regex('/^\d{3}$/');

    // Both should work identically
    expect($patternValidator->validate('123'))->toBe('123');
    expect($regexValidator->validate('123'))->toBe('123');

    // Both should reject invalid values
    $patternValidator->validate('12');
    $regexValidator->validate('12');
})->throws(ValidationException::class);

it('should allow custom error message with regex alias', function () {
    $validator = Validator::isString()->regex('/^\d{3}$/', 'Must be 3 digits');
    $validator->validate('12');
})->throws(ValidationException::class, 'Must be 3 digits');

it('should validate domain strings', function () {
    $validator = Validator::isString()->domain();

    // Valid domains (require at least one dot)
    expect($validator->validate('example.com'))->toBe('example.com');
    expect($validator->validate('subdomain.example.com'))->toBe('subdomain.example.com');
    expect($validator->validate('test-domain.co.uk'))->toBe('test-domain.co.uk');

    // Invalid: single label (no dot)
    $validator->validate('localhost');
})->throws(ValidationException::class, 'Value must be a valid domain name');

it('should reject single label hostnames for domain validation', function () {
    $validator = Validator::isString()->domain();
    $validator->validate('domain');
})->throws(ValidationException::class);

it('should use custom error message for domain validation', function () {
    $validator = Validator::isString()->domain('Please provide a valid domain');
    $validator->validate('localhost');
})->throws(ValidationException::class, 'Please provide a valid domain');

it('should validate IP addresses with IpVersion enum', function () {
    // Any version (default)
    $anyValidator = Validator::isString()->ip();
    expect($anyValidator->validate('192.168.1.1'))->toBe('192.168.1.1');
    expect($anyValidator->validate('2001:0db8::1'))->toBe('2001:0db8::1');

    // Explicit IPv4
    $ipv4Validator = Validator::isString()->ip(IpVersion::IPv4);
    expect($ipv4Validator->validate('192.168.1.1'))->toBe('192.168.1.1');
    expect($ipv4Validator->validate('10.0.0.1'))->toBe('10.0.0.1');
    expect($ipv4Validator->validate('127.0.0.1'))->toBe('127.0.0.1');
    $ipv4Validator->validate('2001:0db8::1');
})->throws(ValidationException::class, 'Value must be a valid IPv4 address');

it('should reject IPv6 addresses for IPv4 validation', function () {
    $validator = Validator::isString()->ip(IpVersion::IPv4);
    $validator->validate('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
})->throws(ValidationException::class);

it('should validate IPv6 addresses with enum', function () {
    $validator = Validator::isString()->ip(IpVersion::IPv6);

    // Valid IPv6
    expect($validator->validate('2001:0db8:85a3:0000:0000:8a2e:0370:7334'))
        ->toBe('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
    expect($validator->validate('2001:db8::1'))->toBe('2001:db8::1');
    expect($validator->validate('::1'))->toBe('::1');
    expect($validator->validate('fe80::1%lo0'))->toBe('fe80::1%lo0');

    // Invalid: IPv4
    $validator->validate('192.168.1.1');
})->throws(ValidationException::class, 'Value must be a valid IPv6 address');

it('should reject IPv4 addresses for IPv6 validation', function () {
    $validator = Validator::isString()->ip(IpVersion::IPv6);
    $validator->validate('192.168.1.1');
})->throws(ValidationException::class);

it('should use custom error message with IP version enum', function () {
    $validator = Validator::isString()->ip(IpVersion::IPv4, 'Must be IPv4 format');
    $validator->validate('not-an-ip');
})->throws(ValidationException::class, 'Must be IPv4 format');

it('should validate UUID version 2', function () {
    $validator = Validator::isString()->uuid(UuidVariant::V2);

    // Valid UUID v2
    expect($validator->validate('6ba7b811-9dad-21d1-80b4-00c04fd430c8'))
        ->toBe('6ba7b811-9dad-21d1-80b4-00c04fd430c8');

    // Invalid: wrong version
    $validator->validate('550e8400-e29b-41d4-a716-446655440000'); // V4
})->throws(ValidationException::class, 'Value must be a valid UUID version 2');

it('should validate UUID version 3', function () {
    $validator = Validator::isString()->uuid(UuidVariant::V3);

    // Valid UUID v3
    expect($validator->validate('6ba7b810-9dad-31d1-80b4-00c04fd430c8'))
        ->toBe('6ba7b810-9dad-31d1-80b4-00c04fd430c8');

    // Invalid: wrong version
    $validator->validate('550e8400-e29b-41d4-a716-446655440000'); // V4
})->throws(ValidationException::class, 'Value must be a valid UUID version 3');

it('should validate UUID version 4', function () {
    $validator = Validator::isString()->uuid(UuidVariant::V4);

    // Valid UUID v4
    expect($validator->validate('550e8400-e29b-41d4-a716-446655440000'))
        ->toBe('550e8400-e29b-41d4-a716-446655440000');

    // Invalid: wrong version
    $validator->validate('6ba7b810-9dad-11d1-80b4-00c04fd430c8'); // V1
})->throws(ValidationException::class, 'Value must be a valid UUID version 4');

it('should validate UUID version 5', function () {
    $validator = Validator::isString()->uuid(UuidVariant::V5);

    // Valid UUID v5
    expect($validator->validate('6ba7b810-9dad-51d1-80b4-00c04fd430c8'))
        ->toBe('6ba7b810-9dad-51d1-80b4-00c04fd430c8');

    // Invalid: wrong version
    $validator->validate('550e8400-e29b-41d4-a716-446655440000'); // V4
})->throws(ValidationException::class, 'Value must be a valid UUID version 5');

it('should validate UUID version 7', function () {
    $validator = Validator::isString()->uuid(UuidVariant::V7);

    // Valid UUID v7
    expect($validator->validate('01890a5d-ac96-7748-b800-303132333435'))
        ->toBe('01890a5d-ac96-7748-b800-303132333435');

    // Invalid: wrong version
    $validator->validate('550e8400-e29b-41d4-a716-446655440000'); // V4
})->throws(ValidationException::class, 'Value must be a valid UUID version 7');

it('should use custom error message with UUID variant enum', function () {
    $validator = Validator::isString()->uuid(UuidVariant::V4, 'Must be UUID v4');
    $validator->validate('not-a-uuid');
})->throws(ValidationException::class, 'Must be UUID v4');
