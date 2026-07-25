<?php

declare(strict_types=1);

namespace Lemmon\Validator;

class FloatValidator extends FieldValidator
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

        if (is_int($value)) {
            // Leave an unsafe int unconverted -- see the {@see isSafeIntegerForFloat()} check in
            // validateType() -- instead of silently widening it into a different, nearby
            // integer's float here on the coerce path.
            return self::isSafeIntegerForFloat($value) ? (float) $value : $value;
        }

        if (is_string($value) && preg_match('/^[+-]?\d+$/', $value)) {
            // Integer-form string (e.g. "9007199254740993"): parse it as an exact PHP int
            // first -- never through a lossy float cast -- so the same safe-integer bound
            // applies here as it does to a raw int input. A decimal or scientific-notation
            // string isn't claiming to be an exact whole number, so it skips this and widens
            // normally below.
            $normalized = preg_replace('/^([+-]?)0+(?=\d)/', '$1', $value);
            $asInt = filter_var($normalized, FILTER_VALIDATE_INT);

            if ($asInt !== false) {
                return self::isSafeIntegerForFloat($asInt) ? (float) $asInt : $value;
            }

            // Too large even for a native int, so definitely beyond the safe float range too --
            // leave it for validateType() to reject rather than risk an unreliable cast.
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
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
        if (!is_float($value) && !is_int($value)) {
            throw self::typeError('Value must be a float', 'float');
        }

        // Integers widen to float, mirroring PHP's own strict_types widening rule (and JSON,
        // where a whole number always decodes to an integer) -- but only when it's a
        // {@see isSafeIntegerForFloat()} value. Beyond +/-2^53 not every integer has an exact
        // float representation, and widening one silently would corrupt it into a different,
        // nearby integer's float instead of validating it (e.g. PHP_INT_MAX and
        // PHP_INT_MAX - 1 both round to the same float).
        if (is_int($value) && !self::isSafeIntegerForFloat($value)) {
            throw self::typeError(
                'Value is too large to be represented as a float without loss of precision',
                'float',
            );
        }

        return (float) $value;
    }
}
