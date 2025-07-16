<?php

namespace Lemmon;

class StringValidator extends FieldValidator
{
    /**
     * @inheritDoc
     */
    protected function coerceValue(mixed $value): mixed
    {
        return is_scalar($value) ? (string) $value : $value;
    }

    /**
     * @inheritDoc
     */
    protected function validateType(mixed $value, string $key): mixed
    {
        if (!is_string($value)) {
            throw new ValidationException(["Field '$key' must be a string."]);
        }
        return $value;
    }
}
