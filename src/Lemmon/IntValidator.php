<?php

namespace Lemmon;

class IntValidator extends FieldValidator
{
    /**
     * @inheritDoc
     */
    protected function coerceValue(mixed $value): mixed
    {
        return is_numeric($value) ? (int) $value : $value;
    }

    /**
     * @inheritDoc
     */
    protected function validateType(mixed $value, string $key): mixed
    {
        if (!is_int($value)) {
            throw new ValidationException(['Value must be an integer.']);
        }
        return $value;
    }
}
