<?php

namespace Lemmon;

/**
 * Trait providing common numeric validation constraints.
 *
 * This trait contains validation methods that are shared between
 * IntValidator and FloatValidator to eliminate code duplication.
 */
trait NumericConstraintsTrait
{
    /**
     * Validates that the value is greater than or equal to the minimum.
     *
     * @param int|float $min The minimum value.
     * @param ?string $message Custom error message.
     * @return static
     */
    public function min(int|float $min, ?string $message = null): static
    {
        return $this->satisfies(
            fn ($value, $key = null, $input = null) => $value >= $min,
            $message ?? "Value must be at least {$min}"
        );
    }

    /**
     * Validates that the value is less than or equal to the maximum.
     *
     * @param int|float $max The maximum value.
     * @param ?string $message Custom error message.
     * @return static
     */
    public function max(int|float $max, ?string $message = null): static
    {
        return $this->satisfies(
            fn ($value, $key = null, $input = null) => $value <= $max,
            $message ?? "Value must be at most {$max}"
        );
    }

    /**
     * Validates that the value is a multiple of the given divisor.
     *
     * @param int|float $divisor The divisor.
     * @param ?string $message Custom error message.
     * @return static
     */
    public function multipleOf(int|float $divisor, ?string $message = null): static
    {
        return $this->satisfies(
            function ($value, $key = null, $input = null) use ($divisor) {
                if (is_int($divisor) && is_int($value)) {
                    return $value % $divisor === 0;
                }

                // Use epsilon comparison for floating-point precision
                $remainder = fmod((float) $value, (float) $divisor);
                $epsilon = 1e-9; // Tolerance for floating-point comparison
                return abs($remainder) < $epsilon || abs($remainder - $divisor) < $epsilon;
            },
            $message ?? "Value must be a multiple of {$divisor}"
        );
    }

    /**
     * Validates that the value is positive (greater than zero).
     *
     * @param ?string $message Custom error message.
     * @return static
     */
    public function positive(?string $message = null): static
    {
        return $this->satisfies(
            fn ($value, $key = null, $input = null) => $value > 0,
            $message ?? 'Value must be positive'
        );
    }

    /**
     * Validates that the value is negative (less than zero).
     *
     * @param ?string $message Custom error message.
     * @return static
     */
    public function negative(?string $message = null): static
    {
        return $this->satisfies(
            fn ($value, $key = null, $input = null) => $value < 0,
            $message ?? 'Value must be negative'
        );
    }
}
