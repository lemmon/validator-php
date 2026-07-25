<?php

declare(strict_types=1);

namespace Lemmon\Validator;

/**
 * A type-agnostic validator that accepts any value without coercion.
 *
 * Used as the base for the {@see Validator::anyOf()}, {@see Validator::allOf()},
 * and {@see Validator::not()} combinators, which compose other validators rather
 * than constraining the value's type themselves.
 *
 * @internal
 */
class MixedValidator extends FieldValidator
{
    /**
     * @inheritDoc
     */
    protected function coerceValue(mixed $value): mixed
    {
        return $value;
    }

    /**
     * @inheritDoc
     */
    protected function validateType(mixed $value, string $key): mixed
    {
        return $value;
    }

    /**
     * @inheritDoc
     */
    protected function getValidatorType(): string
    {
        return 'mixed';
    }
}
