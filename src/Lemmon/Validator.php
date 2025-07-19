<?php

namespace Lemmon;

class Validator
{
    /**
     * Creates a new SchemaValidator for associative array validation.
     *
     * @param array<string, FieldValidator> $schema The schema definition.
     * @return SchemaValidator
     */
    public static function isAssociative(array $schema = []): SchemaValidator
    {
        return new SchemaValidator($schema);
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
}
