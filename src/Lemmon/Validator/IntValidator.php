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

    /**
     * Validates that the value is a valid port number (1-65535).
     *
     * @param ?string $message Custom error message.
     * @return static
     */
    public function port(?string $message = null): static
    {
        return $this->satisfies(
            static fn ($value, $key = null, $input = null) => (
                is_int($value)
                && $value >= 1
                && $value <= 65535
            ),
            $message ?? 'Value must be a valid port number (1-65535)',
        );
    }
}
