<?php

declare(strict_types=1);

namespace Lemmon\Validator;

class IntValidator extends FieldValidator
{
    use NumericConstraintsTrait;
    use AllowedValuesTrait;

    /**
     * @inheritDoc
     */
    protected function coerceValue(mixed $value): mixed
    {
        if ($value === '') {
            return null; // Empty string to null for form safety
        }

        if (!is_string($value)) {
            return $value;
        }

        // Optional sign + digits only, so leading zeros ("08") still coerce -- unlike decimal
        // strings or scientific notation, which FILTER_VALIDATE_INT already rejects below.
        if (!preg_match('/^[+-]?\d+$/', $value)) {
            return $value;
        }

        // FILTER_VALIDATE_INT itself rejects leading zeros, so strip them first and let it
        // handle the actual range check (out-of-range values fail rather than wrap around).
        $normalized = preg_replace('/^([+-]?)0+(?=\d)/', '$1', $value);
        $integer = filter_var($normalized, FILTER_VALIDATE_INT);

        return $integer === false ? $value : $integer;
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
            throw self::typeError('Value must be an integer', 'int');
        }
        return $value;
    }

    /**
     * Validates that the value is a valid port number (1-65535).
     *
     * @param ?string $message Custom error message.
     * @return static
     */
    public function port(?string $message = null): static
    {
        return $this->satisfies(
            static fn(int $value): bool => $value >= 1 && $value <= 65_535,
            $message ?? 'Value must be a valid port number (1-65535)',
            ValidationCode::PORT,
        );
    }
}
