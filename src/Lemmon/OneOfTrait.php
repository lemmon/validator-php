<?php

namespace Lemmon;

/**
 * Trait for validators that support oneOf() validation (value restriction to specific allowed values).
 *
 * This trait should only be used on validators where comparing values with strict equality makes sense:
 * - StringValidator (strings)
 * - IntValidator (integers)
 * - FloatValidator (floats)
 * - BoolValidator (booleans)
 *
 * It should NOT be used on complex types like arrays or objects where equality comparison is problematic.
 */
trait OneOfTrait
{
    /**
     * Restricts the field's value to a specific set of allowed values.
     * Executes in the unified pipeline, respecting the fluent API execution order.
     *
     * @param array<mixed> $values An array of allowed values.
     * @param ?string $message Optional custom error message.
     * @return $this
     */
    public function oneOf(array $values, ?string $message = null): self
    {
        $this->pipeline[] = function ($value) use ($values, $message) {
            if (!in_array($value, $values, true)) {
                throw new ValidationException([$message ?? 'Value must be one of: ' . json_encode($values)]);
            }
            return $value;
        };
        return $this;
    }
}
