<?php

namespace Lemmon;

class BoolValidator extends FieldValidator
{
    /**
     * @inheritDoc
     */
    protected function coerceValue(mixed $value): mixed
    {
        if (in_array(strtolower((string) $value), ['true', 'on', '1'], true)) {
            return true;
        }
        if (in_array(strtolower((string) $value), ['false', 'off', '0'], true)) {
            return false;
        }
        return $value;
    }

    /**
     * @inheritDoc
     */
    protected function validateType(mixed $value, string $key): mixed
    {
        if (!is_bool($value)) {
            throw new ValidationException(['Value must be a boolean.']);
        }
        return $value;
    }
}
