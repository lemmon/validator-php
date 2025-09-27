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
     * @var array<array{rule: callable, message: string}>
     */
    protected array $validations = [];


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
     * Adds a custom validation rule.
     *
     * @param callable $validation The validation function that receives (value, key, input).
     * @param string $message The error message if validation fails.
     * @return $this
     */
    public function addValidation(callable $validation, string $message): self
    {
        $this->validations[] = ['rule' => $validation, 'message' => $message];
        return $this;
    }

    /**
     * Validates that the value passes ALL of the provided validators.
     *
     * @param array<FieldValidator> $validators Array of validators that must all pass.
     * @param ?string $message Custom error message.
     * @return $this
     */
    public function allOf(array $validators, ?string $message = null): self
    {
        return $this->addValidation(
            function ($value, $key = null, $input = null) use ($validators) {
                foreach ($validators as $validator) {
                    [$valid, , ] = $validator->tryValidate($value, $key, $input);
                    if (!$valid) {
                        return false;
                    }
                }
                return true;
            },
            $message ?? 'Value must satisfy all validation rules'
        );
    }

    /**
     * Validates that the value passes ANY of the provided validators.
     *
     * @param array<FieldValidator> $validators Array of validators, at least one must pass.
     * @param ?string $message Custom error message.
     * @return $this
     */
    public function anyOf(array $validators, ?string $message = null): self
    {
        return $this->addValidation(
            function ($value, $key = null, $input = null) use ($validators) {
                foreach ($validators as $validator) {
                    [$valid, , ] = $validator->tryValidate($value, $key, $input);
                    if ($valid) {
                        return true;
                    }
                }
                return false;
            },
            $message ?? 'Value must satisfy at least one validation rule'
        );
    }

    /**
     * Validates that the value does NOT pass the provided validator.
     *
     * @param FieldValidator $validator The validator that must fail.
     * @param ?string $message Custom error message.
     * @return $this
     */
    public function not(FieldValidator $validator, ?string $message = null): self
    {
        return $this->addValidation(
            function ($value, $key = null, $input = null) use ($validator) {
                [$valid, , ] = $validator->tryValidate($value, $key, $input);
                return !$valid;
            },
            $message ?? 'Value must not satisfy the validation rule'
        );
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
            throw new ValidationException($errors ?? ['Validation failed']);
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
            return $this->hasDefault ? [true, $this->default, null] : ($this->required ? [false, $value, ['Value is required']] : [true, null, null]);
        }

        $value = $this->coerce ? $this->coerceValue($value) : $value;

        if ($this->oneOf && !in_array($value, $this->oneOf, true)) {
            return [false, $value, ['Value must be one of: ' . json_encode($this->oneOf)]];
        }

        try {
            $validatedValue = $this->validateType($value, $key);

            // Collect all validation errors
            $validationErrors = [];
            foreach ($this->validations as $validation) {
                if (!$validation['rule']($validatedValue, $key, $input)) {
                    $validationErrors[] = $validation['message'];
                }
            }

            if (!empty($validationErrors)) {
                return [false, $validatedValue, $validationErrors];
            }

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
