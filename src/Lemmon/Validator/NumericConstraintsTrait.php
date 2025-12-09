<?php

declare(strict_types=1);

namespace Lemmon\Validator;

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
    public function min(int|float $min, null|string $message = null): static
    {
        return $this->satisfies(
            fn($value, $key = null, $input = null) => $value >= $min,
            $message ?? "Value must be at least {$min}",
        );
    }

    /**
     * Validates that the value is less than or equal to the maximum.
     *
     * @param int|float $max The maximum value.
     * @param ?string $message Custom error message.
     * @return static
     */
    public function max(int|float $max, null|string $message = null): static
    {
        return $this->satisfies(
            fn($value, $key = null, $input = null) => $value <= $max,
            $message ?? "Value must be at most {$max}",
        );
    }

    /**
     * Validates that the value is greater than the given threshold.
     *
     * @param int|float $threshold The minimum exclusive value.
     * @param ?string $message Custom error message.
     * @return static
     */
    public function gt(int|float $threshold, null|string $message = null): static
    {
        return $this->satisfies(
            fn($value, $key = null, $input = null) => $value > $threshold,
            $message ?? "Value must be greater than {$threshold}",
        );
    }

    /**
     * Validates that the value is greater than or equal to the given threshold.
     *
     * @param int|float $threshold The minimum inclusive value.
     * @param ?string $message Custom error message.
     * @return static
     */
    public function gte(int|float $threshold, null|string $message = null): static
    {
        return $this->satisfies(
            fn($value, $key = null, $input = null) => $value >= $threshold,
            $message ?? "Value must be at least {$threshold}",
        );
    }

    /**
     * Validates that the value is less than the given threshold.
     *
     * @param int|float $threshold The maximum exclusive value.
     * @param ?string $message Custom error message.
     * @return static
     */
    public function lt(int|float $threshold, null|string $message = null): static
    {
        return $this->satisfies(
            fn($value, $key = null, $input = null) => $value < $threshold,
            $message ?? "Value must be less than {$threshold}",
        );
    }

    /**
     * Validates that the value is less than or equal to the given threshold.
     *
     * @param int|float $threshold The maximum inclusive value.
     * @param ?string $message Custom error message.
     * @return static
     */
    public function lte(int|float $threshold, null|string $message = null): static
    {
        return $this->satisfies(
            fn($value, $key = null, $input = null) => $value <= $threshold,
            $message ?? "Value must be at most {$threshold}",
        );
    }

    /**
     * Validates that the value is a multiple of the given divisor.
     *
     * @param int|float $divisor The divisor.
     * @param ?string $message Custom error message.
     * @return static
     */
    public function multipleOf(int|float $divisor, null|string $message = null): static
    {
        return $this->satisfies(
            function ($value, $key = null, $input = null) use ($divisor) {
                if (is_int($divisor) && is_int($value)) {
                    return ($value % $divisor) === 0;
                }

                // Use epsilon comparison for floating-point precision
                $remainder = fmod((float) $value, (float) $divisor);
                $epsilon = 1e-9; // Tolerance for floating-point comparison
                return abs($remainder) < $epsilon || abs($remainder - $divisor) < $epsilon;
            },
            $message ?? "Value must be a multiple of {$divisor}",
        );
    }

    /**
     * Validates that the value is positive (greater than zero).
     *
     * @param ?string $message Custom error message.
     * @return static
     */
    public function positive(null|string $message = null): static
    {
        return $this->satisfies(
            fn($value, $key = null, $input = null) => $value > 0,
            $message ?? 'Value must be positive',
        );
    }

    /**
     * Validates that the value is negative (less than zero).
     *
     * @param ?string $message Custom error message.
     * @return static
     */
    public function negative(null|string $message = null): static
    {
        return $this->satisfies(
            fn($value, $key = null, $input = null) => $value < 0,
            $message ?? 'Value must be negative',
        );
    }

    /**
     * Validates that the value is non-negative (zero or greater).
     *
     * @param ?string $message Custom error message.
     * @return static
     */
    public function nonNegative(null|string $message = null): static
    {
        return $this->gte(0, $message ?? 'Value must be non-negative');
    }

    /**
     * Validates that the value is non-positive (zero or less).
     *
     * @param ?string $message Custom error message.
     * @return static
     */
    public function nonPositive(null|string $message = null): static
    {
        return $this->lte(0, $message ?? 'Value must be non-positive');
    }

    /**
     * Clamps the value within the provided range.
     *
     * @param int|float $min Minimum allowed value.
     * @param int|float $max Maximum allowed value.
     * @return static
     */
    public function clampToRange(int|float $min, int|float $max): static
    {
        if ($min > $max) {
            throw new \InvalidArgumentException('Minimum cannot be greater than maximum for clamp');
        }

        return $this->pipe(function ($value) use ($min, $max) {
            if ($value < $min) {
                return $min;
            }
            if ($value > $max) {
                return $max;
            }
            return $value;
        });
    }
}
