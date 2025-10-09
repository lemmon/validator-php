<?php

namespace Lemmon;

class IntValidator extends FieldValidator
{
    use NumericConstraintsTrait;

    /**
     * @inheritDoc
     */
    protected function coerceValue(mixed $value): mixed
    {
        if ($value === '') {
            return null; // Empty string to null for form safety
        }
        return is_numeric($value) ? (int) $value : $value;
    }

    /**
     * @inheritDoc
     */
    protected function getValidatorType(): string
    {
        return 'int';
    }

    /**
     * @inheritDoc
     */
    protected function validateType(mixed $value, string $key): mixed
    {
        if (!is_int($value)) {
            throw new ValidationException(['Value must be an integer']);
        }
        return $value;
    }
}
