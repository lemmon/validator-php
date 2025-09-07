<?php

namespace Lemmon;

abstract class FieldValidator
{
    protected bool $required = false;
    protected mixed $default = null;
    protected bool $hasDefault = false;
    protected bool $coerce = false;
    protected bool $nullifyEmpty = false;
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
     * Enables nullification of empty values (empty string, empty array) to null.
     *
     * @return $this
     */
    public function nullifyEmpty(): self
    {
        $this->nullifyEmpty = true;
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
    public function validate(mixed $value, string $key = '', array $input = []): mixed
    {
        [$valid, $data, $errors] = $this->tryValidate($value, $key, $input);
        if (!$valid) {
            throw new ValidationException($errors ?? ['Validation failed.']);
        }
        return $data;
    }

    /**
     * Tries to validate the given value and returns a result tuple.
     *
     * @param mixed $value The value to validate.
     * @param string $key The key of the field being validated.
     * @param mixed|null $input The entire input payload (array or object).
     * @return array{bool, mixed, array<string>|null} A tuple containing:
     *                                                 - bool: true if validation is successful, false otherwise.
     *                                                 - mixed: The validated and potentially coerced value, or the original value on failure.
     *                                                 - array|null: An array of error messages on failure, or null on success.
     */
    public function tryValidate(mixed $value, string $key = '', mixed $input = null): array
    {
        if ($this->nullifyEmpty && (($value === '') || (is_array($value) && empty($value)))) {
            $value = null;
        }

        if (is_null($value)) {
            return $this->hasDefault ? [true, $this->default, null] : ($this->required ? [false, $value, ['Value is required.']] : [true, null, null]);
        }

        $value = $this->coerce ? $this->coerceValue($value) : $value;

        if ($this->oneOf && !in_array($value, $this->oneOf, true)) {
            return [false, $value, ['Value must be one of: ' . json_encode($this->oneOf)]];
        }

        try {
            $validatedValue = $this->validateType($value, $key);
            return [true, $validatedValue, null];
        } catch (ValidationException $e) {
            return [false, $value, $e->getErrors()];
        }
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
