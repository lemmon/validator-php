<?php

namespace Lemmon;

class FloatValidator extends FieldValidator
{
    use NumericConstraintsTrait;

    /**
     * @inheritDoc
     */
    protected function coerceValue(mixed $value): mixed
    {
        if ($value === '') {
            return 0.0;
        }
        if (is_numeric($value)) {
            return is_int($value) ? (float) $value : (float) $value;
        }
        return $value;
    }

    /**
     * @inheritDoc
     */
    protected function validateType(mixed $value, string $key): mixed
    {
        if (!is_numeric($value)) {
            throw new ValidationException(['Value must be a float']);
        }
        return (float) $value;
    }
}
