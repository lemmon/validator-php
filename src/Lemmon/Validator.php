<?php

namespace Lemmon;

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
}
