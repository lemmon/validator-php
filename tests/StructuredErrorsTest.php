<?php

declare(strict_types=1);

use Lemmon\Tests\Fixtures\ThrowingJsonSerializable;
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

it('builds dotted paths through nested schemas via getErrors', function () {
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
        $errors = $e->getErrors();
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
        $errors = $e->getErrors();
        $paths = array_map(static fn(ValidationError $err) => $err->getPath(), $errors);
        $codes = array_map(static fn(ValidationError $err) => $err->getCode(), $errors);

        expect($paths)->toBe(['links.0.slug', 'links.1.slug']);
        expect($codes)->toBe([ValidationCode::NOT_UNIQUE, ValidationCode::NOT_UNIQUE]);

        // Params carry the duplicate value and the conflicting indices for i18n re-rendering
        expect($errors[0]->getParams())->toBe(['field' => 'slug', 'value' => 'a', 'others' => [1]]);
        expect($errors[1]->getParams())->toBe(['field' => 'slug', 'value' => 'a', 'others' => [0]]);
    }
});

it('interpolates {field}/{value}/{others} placeholders in a custom uniqueField message', function () {
    $validator = Validator::isArray()
        ->items(Validator::isAssociative(['email' => Validator::isString()->required()]))
        ->uniqueField('email', 'Duplicate {field} {value} (also at {others})');

    [, , $errors] = $validator->tryValidate([
        ['email' => 'a@b.com'],
        ['email' => 'a@b.com'],
    ]);

    expect($errors[0]->getMessage())->toBe('Duplicate email a@b.com (also at [1])');
    expect($errors[1]->getMessage())->toBe('Duplicate email a@b.com (also at [0])');
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

it('records the searched value in params for scalar contains()', function () {
    [, , $errors] = Validator::isArray()->contains('needle')->tryValidate(['a', 'b']);

    expect($errors[0]->getCode())->toBe(ValidationCode::CONTAINS);
    expect($errors[0]->getParams())->toBe(['value' => 'needle']);
});

it('omits value params for the validator form of contains()', function () {
    [, , $errors] = Validator::isArray()
        ->contains(Validator::isInt()->min(10))
        ->tryValidate([1, 2, 3]);

    expect($errors[0]->getCode())->toBe(ValidationCode::CONTAINS);
    expect($errors[0]->getParams())->toBe([]);
});

// A param value that cannot be rendered (a throwing jsonSerialize(), a non-stringable object) must
// never escalate into breaking validation control flow: tryValidate() must still return its tuple
// and validate() must still throw a ValidationException.

it('does not let an unserializable satisfies() param break tryValidate()', function () {
    $validator = Validator::isString()->satisfies(
        static fn($v) => false,
        'always fails',
        'CUSTOM_CODE',
        ['evil' => new ThrowingJsonSerializable()],
    );

    [$valid, , $errors] = $validator->tryValidate('x');

    expect($valid)->toBe(false);
    expect($errors[0]->getCode())->toBe('CUSTOM_CODE');
});

it('throws a ValidationException (not the raw throwable) for an unserializable param', function () {
    $validator = Validator::isString()->satisfies(
        static fn($v) => false,
        'always fails',
        'CUSTOM_CODE',
        ['evil' => new ThrowingJsonSerializable()],
    );

    expect(static fn() => $validator->validate('x'))->toThrow(ValidationException::class);
});

it('does not let an unserializable uniqueField value break tryValidate()', function () {
    $bomb = new ThrowingJsonSerializable();

    [$valid, , $errors] = Validator::isArray()
        ->uniqueField('id')
        ->tryValidate([['id' => $bomb], ['id' => $bomb]]);

    expect($valid)->toBe(false);
    expect($errors[0]->getCode())->toBe(ValidationCode::NOT_UNIQUE);
    expect($errors[0]->getMessage())->toContain('(complex value)');
});

it('renders an unserializable uniqueField value as (complex value) in a custom message too', function () {
    $bomb = new ThrowingJsonSerializable();

    // The default message and an interpolated {value} placeholder must render an unrenderable
    // value the same way, rather than the custom path silently collapsing it to an empty string.
    [, , $errors] = Validator::isArray()
        ->uniqueField('id', 'Duplicate {value}')
        ->tryValidate([['id' => $bomb], ['id' => $bomb]]);

    expect($errors[0]->getMessage())->toBe('Duplicate (complex value)');
});

it('collapses an un-encodable param value to (complex value) in the JSON payload', function () {
    // The mirror of type preservation: a param that cannot be JSON-encoded (here a throwing
    // jsonSerialize()) is replaced by the placeholder rather than dropped or left to break encoding.
    $bomb = new ThrowingJsonSerializable();

    [, , $errors] = Validator::isArray()
        ->uniqueField('id')
        ->tryValidate([['id' => $bomb], ['id' => $bomb]]);

    $decoded = json_decode(json_encode($errors), true);
    expect($decoded[0]['params']['value'])->toBe('(complex value)');
});

it('preserves encodable param types through json_encode() on the error list', function () {
    // The safety net must not blanket-stringify clean params: a numeric `min` stays an int.
    [, , $errors] = Validator::isString()->minLength(5)->tryValidate('hi');

    $decoded = json_decode(json_encode($errors), true);
    expect($decoded[0]['params'])->toBe(['min' => 5]);
});

it('does not let an unserializable uniqueField field value crash tryValidate()', function () {
    // serialize() throws on a closure; the failure must not escape uniqueField. The same instance
    // appearing twice still collides (identity fallback), so it is reported as a duplicate.
    $closure = static fn() => 1;

    [$valid, , $errors] = Validator::isArray()
        ->uniqueField('id')
        ->tryValidate([['id' => $closure], ['id' => $closure]]);

    expect($valid)->toBe(false);
    expect($errors[0]->getCode())->toBe(ValidationCode::NOT_UNIQUE);
});

it('treats distinct unserializable field instances as unique', function () {
    [$valid] = Validator::isArray()
        ->uniqueField('id')
        ->tryValidate([['id' => static fn() => 1], ['id' => static fn() => 2]]);

    expect($valid)->toBe(true);
});

it('does not re-invoke a param jsonSerialize() during encoding (no second-call leak)', function () {
    // A param whose jsonSerialize() encodes cleanly once but throws on the next call must not leak:
    // jsonSafe() normalises the probed value, so the outer encoder never calls user code again. The
    // throwable would otherwise escape construction -- and tryValidate(), which only catches
    // ValidationException -- as a raw RuntimeException.
    $flaky = new class implements JsonSerializable {
        private int $calls = 0;

        public function jsonSerialize(): mixed
        {
            if ($this->calls++ === 0) {
                return ['ok' => true];
            }

            throw new RuntimeException('second call boom');
        }
    };

    $error = new ValidationError('', 'CUSTOM_CODE', 'nope', ['flaky' => $flaky]);

    // Encoding the error list for the exception message must not leak the throwable.
    $exception = new ValidationException([$error]);
    expect($exception)->toBeInstanceOf(ValidationException::class);

    // And the advertised payload stays safe on a subsequent encode.
    expect(json_encode([$error]))->toBeString();
});

// The invariant, asserted in one place instead of per value-shape: whatever a rule puts into an
// error -- a duplicate value, a satisfies() param, a custom message -- the structured error list
// is always JSON-encodable (the advertised `json_encode($e->getErrors())` payload) and every
// message is valid UTF-8. New hostile value shapes are caught here rather than shipping.
it('always yields a JSON-safe error list with valid-UTF-8 messages', function (Closure $produceErrors) {
    /** @var array<ValidationError> $errors */
    $errors = $produceErrors();

    expect($errors)->not->toBeEmpty();

    // The advertised API payload must never throw or return false, under default json_encode flags.
    $json = json_encode($errors);
    expect($json)->toBeString();
    expect(json_last_error())->toBe(JSON_ERROR_NONE);

    // Every stored message is valid UTF-8, so getMessage() is always safe to render or re-encode.
    foreach ($errors as $error) {
        expect(preg_match('//u', $error->getMessage()))->toBe(1);
    }
})->with([
    'invalid UTF-8 in a duplicate field value' => [
        fn() => (
            Validator::isArray()
                ->uniqueField('name')
                ->tryValidate([['name' => "\xC3\x28"], ['name' => "\xC3\x28"]])[2] ?? []
        ),
    ],
    'NAN in a duplicate field value' => [
        fn() => (
            Validator::isArray()
                ->uniqueField('q')
                ->tryValidate([['q' => NAN], ['q' => NAN]])[2] ?? []
        ),
    ],
    'throwing jsonSerialize() in a duplicate field value' => [
        fn() => (
            Validator::isArray()
                ->uniqueField('id')
                ->tryValidate([['id' => new ThrowingJsonSerializable()], ['id' => new ThrowingJsonSerializable()]])[2]
            ?? []
        ),
    ],
    'throwing jsonSerialize() in a satisfies() param' => [
        fn() => (
            Validator::isString()
                ->satisfies(static fn() => false, 'nope', 'X', ['evil' => new ThrowingJsonSerializable()])
                ->tryValidate('whatever')[2] ?? []
        ),
    ],
    'a resource in a satisfies() param' => [
        fn() => (
            Validator::isString()
                ->satisfies(static fn() => false, 'nope', 'X', ['handle' => fopen('php://memory', 'r')])
                ->tryValidate('whatever')[2] ?? []
        ),
    ],
    'invalid UTF-8 in a custom message' => [
        fn() => (
            Validator::isString()
                ->minLength(5, "bad \xC3\x28 message")
                ->tryValidate('hi')[2] ?? []
        ),
    ],
]);
