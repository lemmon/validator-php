<?php

namespace Lemmon;

abstract class FieldValidator
{
    protected bool $required = false;
    protected mixed $default = null;
    protected bool $hasDefault = false;
    protected bool $coerce = false;
    /**
     * @var array<mixed>|null
     */
    protected $oneOf = null;

    /**
     * Marks the field as required.
     *
     * @return $this
     */
    public function required(): self
    {
        $this->required = true;
        return $this;
    }

    /**
     * Sets a default value for the field if it's missing or null.
     *
     * @param mixed $value The default value.
     * @return $this
     */
    public function default(mixed $value): self
    {
        $this->default = $value;
        $this->hasDefault = true;
        return $this;
    }

    /**
     * Enables smart coercion of string inputs to the target type.
     *
     * @return $this
     */
    public function coerce(): self
    {
        $this->coerce = true;
        return $this;
    }

    /**
     * Restricts the field's value to a specific set of allowed values.
     *
     * @param array<mixed> $values An array of allowed values.
     * @return $this
     */
    public function oneOf(array $values): self
    {
        $this->oneOf = $values;
        return $this;
    }

    /**
     * Validates the given value against the defined rules.
     *
     * @param mixed $value The value to validate.
     * @param string $key The key of the field being validated.
     * @param array<string, mixed> $input The entire input array.
     * @return mixed The validated and potentially coerced value.
     * @throws ValidationException If validation fails.
     */
    public function validate(mixed $value, string $key, array $input): mixed
    {
        if (is_null($value)) {
            if ($this->hasDefault) {
                return $this->default;
            }
            if ($this->required) {
                throw new ValidationException(["Field '$key' is required."]);
            }
            return null;
        }

        $value = $this->coerce ? $this->coerceValue($value) : $value;

        if ($this->oneOf && !in_array($value, $this->oneOf, true)) {
            throw new ValidationException(["Field '$key' must be one of: " . implode(', ', $this->oneOf)]);
        }

        return $this->validateType($value, $key);
    }

    /**
     * Coerces the value to the appropriate type.
     *
     * @param mixed $value The value to coerce.
     * @return mixed The coerced value.
     */
    abstract protected function coerceValue(mixed $value): mixed;

    /**
     * Validates the type of the value.
     *
     * @param mixed $value The value to validate.
     * @param string $key The key of the field being validated.
     * @return mixed The validated value.
     * @throws ValidationException If the type validation fails.
     */
    abstract protected function validateType(mixed $value, string $key): mixed;
}
