<?php

declare(strict_types=1);

use Lemmon\Tests\Fixtures\ColorEnum;
use Lemmon\Tests\Fixtures\StatusEnum;
use Lemmon\Validator\InstanceValidator;
use Lemmon\Validator\ValidationCode;
use Lemmon\Validator\ValidationException;
use Lemmon\Validator\Validator;

it('should create an InstanceValidator from the factory', function () {
    expect(Validator::isInstance(DateTimeImmutable::class))->toBeInstanceOf(InstanceValidator::class);
});

it('should accept an instance of the class and return it unchanged', function () {
    $date = new DateTimeImmutable('2026-10-06');

    expect(Validator::isInstance(DateTimeImmutable::class)->validate($date))->toBe($date);
});

it('should accept subclasses and interface implementations', function () {
    $subclass = new class('2026-10-06') extends DateTimeImmutable {};

    expect(Validator::isInstance(DateTimeImmutable::class)->validate($subclass))->toBe($subclass);
    expect(Validator::isInstance(DateTimeInterface::class)->validate(new DateTime()))->toBeInstanceOf(DateTime::class);
    expect(Validator::isInstance(Countable::class)->validate(new ArrayObject()))->toBeInstanceOf(ArrayObject::class);
});

it('should accept enum cases', function () {
    expect(Validator::isInstance(StatusEnum::class)->validate(StatusEnum::Active))->toBe(StatusEnum::Active);
    expect(Validator::isInstance(UnitEnum::class)->validate(ColorEnum::Red))->toBe(ColorEnum::Red);
});

it('should reject values that are not instances with INVALID_TYPE', function (mixed $value) {
    [$valid, , $errors] = Validator::isInstance(DateTimeInterface::class)->tryValidate($value);

    expect($valid)->toBeFalse();
    expect($errors)->toHaveCount(1);
    expect($errors[0]->getPath())->toBe('');
    expect($errors[0]->getCode())->toBe(ValidationCode::INVALID_TYPE);
    expect($errors[0]->getMessage())->toBe('Value must be an instance of DateTimeInterface');
    expect($errors[0]->getParams())->toBe(['expected' => 'DateTimeInterface']);
})->with([
    'other object' => [new ArrayObject()],
    'stdClass' => [new stdClass()],
    'date string' => ['2026-10-06'],
    'int' => [1_759_708_800],
    'array' => [['date' => '2026-10-06']],
    'enum case' => [StatusEnum::Active],
]);

it('should normalize a leading backslash in the class name', function () {
    [, , $errors] = Validator::isInstance('\\DateTimeInterface')->tryValidate('nope');

    expect($errors[0]->getParams())->toBe(['expected' => 'DateTimeInterface']);
    expect(Validator::isInstance('\\DateTimeInterface')->validate(new DateTime()))->toBeInstanceOf(DateTime::class);
});

it('should throw InvalidArgumentException for an unknown class name', function () {
    expect(fn() => Validator::isInstance('App\\DoesNotExist'))
        ->toThrow(
            InvalidArgumentException::class,
            "Class, interface, or enum 'App\\DoesNotExist' does not exist",
        );
});

it('should allow null unless required', function () {
    expect(Validator::isInstance(DateTimeInterface::class)->validate(null))->toBeNull();

    [, , $errors] = Validator::isInstance(DateTimeInterface::class)->required()->tryValidate(null);
    expect($errors[0]->getCode())->toBe(ValidationCode::REQUIRED);
});

it('should apply a fresh default per run with defaultUsing()', function () {
    $validator = Validator::isInstance(ArrayObject::class)->defaultUsing(static fn() => new ArrayObject());

    $first = $validator->validate(null);
    $second = $validator->validate(null);

    expect($first)->toBeInstanceOf(ArrayObject::class);
    expect($first)->not->toBe($second);
});

it('should coerce only an empty string to null', function () {
    $validator = Validator::isInstance(DateTimeInterface::class)->coerce();

    expect($validator->validate(''))->toBeNull();
    expect(fn() => $validator->validate('2026-10-06'))->toThrow(ValidationException::class);
    expect(fn() => $validator->required()->validate(''))->toThrow(ValidationException::class);
});

it('should hand the typed instance to later pipeline steps', function () {
    $validator = Validator::isInstance(DateTimeInterface::class)
        ->satisfies(
            static fn(DateTimeInterface $date): bool => $date->format('Y') >= '2000',
            'Date must be in 2000 or later',
        )
        ->transform(static fn(DateTimeInterface $date): string => $date->format('Y-m-d'));

    expect($validator->validate(new DateTimeImmutable('2026-10-06')))->toBe('2026-10-06');

    [, , $errors] = $validator->tryValidate(new DateTimeImmutable('1999-12-31'));
    expect($errors[0]->getMessage())->toBe('Date must be in 2000 or later');
});

it('should report instance errors at the field path inside a schema', function () {
    $schema = Validator::isAssociative([
        'published_at' => Validator::isInstance(DateTimeInterface::class)->required(),
    ]);

    [, , $errors] = $schema->tryValidate(['published_at' => '2026-10-06']);

    expect($errors)->toHaveCount(1);
    expect($errors[0]->getSegments())->toBe(['published_at']);
    expect($errors[0]->getCode())->toBe(ValidationCode::INVALID_TYPE);
    expect($errors[0]->getParams())->toBe(['expected' => 'DateTimeInterface']);
});

it('should keep the class constraint when cloned', function () {
    $original = Validator::isInstance(DateTimeInterface::class);
    $copy = $original->clone()->required();

    expect($copy->validate(new DateTime()))->toBeInstanceOf(DateTime::class);
    expect(fn() => $copy->validate(new ArrayObject()))->toThrow(ValidationException::class);
    expect($original->validate(null))->toBeNull();
});
