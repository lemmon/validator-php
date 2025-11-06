<?php

namespace Lemmon\Validator;

class FloatValidator extends FieldValidator
{
    use NumericConstraintsTrait;
    use OneOfTrait;

    /**
     * @inheritDoc
     */
    protected function coerceValue(mixed $value): mixed
    {
        if ($value === '') {
            return null; // Empty string to null for form safety
        }
        if (is_numeric($value)) {
            return is_int($value) ? (float) $value : (float) $value;
        }
        return $value;
    }

    /**
     * @inheritDoc
     */
    protected function getValidatorType(): string
    {
        return 'float';
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
