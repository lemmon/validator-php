<?php

namespace Lemmon;

class BoolValidator extends FieldValidator
{
    use OneOfTrait;
    /**
     * @inheritDoc
     */
    protected function coerceValue(mixed $value): mixed
    {
        if ($value === '') {
            return null; // Empty string to null for form safety
        }
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
    protected function getValidatorType(): string
    {
        return 'bool';
    }

    /**
     * @inheritDoc
     */
    protected function validateType(mixed $value, string $key): mixed
    {
        if (!is_bool($value)) {
            throw new ValidationException(['Value must be a boolean']);
        }
        return $value;
    }
}
