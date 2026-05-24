<?php

declare(strict_types=1);

namespace Lemmon\Validator;

class Validator
{
    /**
     * Creates a new AssociativeValidator for associative array validation.
     *
     * @param array<string, FieldValidator> $schema The schema definition.
     * @return AssociativeValidator
     */
    public static function isAssociative(array $schema = []): AssociativeValidator
    {
        return new AssociativeValidator($schema);
    }

    /**
     * Creates a new ObjectValidator for stdClass object validation.
     *
     * @param array<string, FieldValidator> $schema The schema definition.
     * @return ObjectValidator
     */
    public static function isObject(array $schema = []): ObjectValidator
    {
        return new ObjectValidator($schema);
    }

    /**
     * Creates a new ArrayValidator for plain array validation.
     *
     * @return ArrayValidator
     */
    public static function isArray(): ArrayValidator
    {
        return new ArrayValidator();
    }

    /**
     * Creates a new StringValidator.
     *
     * @return StringValidator
     */
    public static function isString(): StringValidator
    {
        return new StringValidator();
    }

    /**
     * Creates a new IntValidator.
     *
     * @return IntValidator
     */
    public static function isInt(): IntValidator
    {
        return new IntValidator();
    }

    /**
     * Creates a new BoolValidator.
     *
     * @return BoolValidator
     */
    public static function isBool(): BoolValidator
    {
        return new BoolValidator();
    }

    /**
     * Creates a new FloatValidator for floating-point numbers.
     *
     * @return FloatValidator
     */
    public static function isFloat(): FloatValidator
    {
        return new FloatValidator();
    }

    /**
     * Creates a validator that passes if ANY of the provided validators pass.
     *
     * @param array<FieldValidator> $validators Array of validators, at least one must pass.
     * @param ?string $message Custom error message.
     * @return FieldValidator
     */
    public static function anyOf(array $validators, ?string $message = null): FieldValidator
    {
        return (new MixedValidator())->satisfiesAny($validators, $message);
    }

    /**
     * Creates a validator that passes if ALL of the provided validators pass.
     *
     * @param array<FieldValidator> $validators Array of validators that must all pass.
     * @param ?string $message Custom error message.
     * @return FieldValidator
     */
    public static function allOf(array $validators, ?string $message = null): FieldValidator
    {
        return (new MixedValidator())->satisfiesAll($validators, $message);
    }

    /**
     * Creates a validator that passes if the provided validator does NOT pass.
     *
     * @param FieldValidator $validator The validator that must fail.
     * @param ?string $message Custom error message.
     * @return FieldValidator
     */
    public static function not(FieldValidator $validator, ?string $message = null): FieldValidator
    {
        return (new MixedValidator())
            ->satisfiesNone([$validator], $message ?? 'Value must not satisfy the validation rule');
    }
}
