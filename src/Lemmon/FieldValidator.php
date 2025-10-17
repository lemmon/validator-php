<?php

namespace Lemmon;

abstract class FieldValidator
{
    protected mixed $default = null;
    protected bool $hasDefault = false;
    protected bool $coerce = false;
    /**
     * @var array<mixed>|null
     */
    protected $oneOf = null;
    /**
     * @var array<array{rule: callable, message: string}>
     */
    protected array $validations = [];
    /**
     * @var array<callable>
     */
    protected array $transformations = [];
    /**
     * Current type context for transformations (null = original validator type)
     */
    protected ?string $currentType = null;


    /**
     * Marks the field as required.
     *
     * @param string|null $message Custom error message for required validation
     * @return $this
     */
    public function required(?string $message = null): self
    {
        $this->transformations[] = function ($value) use ($message) {
            if (is_null($value)) {
                throw new ValidationException([$message ?? 'Value is required']);
            }
            return $value;
        };
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
        $this->transformations[] = function ($value) {
            return (($value === '') || (is_array($value) && empty($value))) ? null : $value;
        };
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
     * Adds a custom validation rule with an optional error message.
     *
     * @param callable $validation The validation function that receives (value, key, input) parameters.
     * @param ?string $message Optional custom error message. If not provided, a generic message is used.
     * @return static
     */
    public function satisfies(callable $validation, ?string $message = null): self
    {
        $this->validations[] = [
            'rule' => $validation,
            'message' => $message ?? 'Custom validation failed'
        ];
        return $this;
    }

    /**
     * @deprecated Use satisfies() instead. Will be removed in v1.0.0.
     */
    public function addValidation(callable $validation, string $message): self
    {
        return $this->satisfies($validation, $message);
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
     * Adds a transformation function to be applied after successful validation.
     * Can change the type - subsequent operations work with the new type.
     *
     * @param callable $transformer The transformation function that receives the validated value.
     * @return $this
     */
    public function transform(callable $transformer): self
    {
        $this->transformations[] = function ($value) use ($transformer) {
            $result = $transformer($value);

            // Update current type context based on result
            $this->currentType = $this->detectType($result);

            return $result; // No coercion - transform can change type
        };
        return $this;
    }

    /**
     * Adds multiple transformation functions to be applied after successful validation.
     * Maintains the current type - applies type-specific coercion to results.
     *
     * @param callable ...$transformers The transformation functions (variadic arguments).
     * @return $this
     */
    public function pipe(callable ...$transformers): self
    {
        foreach ($transformers as $transformer) {
            $this->transformations[] = function ($value) use ($transformer) {
                $result = $transformer($value);

                // Apply type-specific coercion based on current type context
                return $this->coerceForCurrentType($result);
            };
        }
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

        // Handle initial null values - only return early if no transformations and no default
        if (is_null($value) && empty($this->transformations)) {
            return $this->hasDefault ? [true, $this->default, null] : [true, null, null];
        }

        $value = $this->coerce ? $this->coerceValue($value) : $value;

        // Handle null values after coercion - only return early if no transformations and no default
        if (is_null($value) && empty($this->transformations)) {
            return $this->hasDefault ? [true, $this->default, null] : [true, null, null];
        }

        if ($this->oneOf && !in_array($value, $this->oneOf, true)) {
            return [false, $value, ['Value must be one of: ' . json_encode($this->oneOf)]];
        }

        try {
            // Skip type validation for null values if we have transformations (let transformations handle it)
            $validatedValue = is_null($value) && !empty($this->transformations) ? $value : $this->validateType($value, $key);

            // Collect all validation errors (skip for null values that will be handled by transformations)
            $validationErrors = [];
            if (!is_null($validatedValue) || empty($this->transformations)) {
                foreach ($this->validations as $validation) {
                    if (!$validation['rule']($validatedValue, $key, $input)) {
                        $validationErrors[] = $validation['message'];
                    }
                }
            }

            if (!empty($validationErrors)) {
                return [false, $validatedValue, $validationErrors];
            }

            // Apply transformations after successful validation
            $transformedValue = $validatedValue;
            foreach ($this->transformations as $transformation) {
                $transformedValue = $transformation($transformedValue);
            }

            return [true, $transformedValue, null];
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

    /**
     * Returns the type that this validator represents.
     *
     * @return string The validator type (e.g., 'string', 'int', 'indexed_array', 'associative_array')
     */
    abstract protected function getValidatorType(): string;

    /**
     * Gets the current type context for transformations.
     *
     * @return string The current type or the validator's original type
     */
    protected function getCurrentType(): string
    {
        return $this->currentType ?? $this->getValidatorType();
    }

    /**
     * Detects the type of a value for transformation context.
     *
     * @param mixed $value The value to analyze
     * @return string The detected type
     */
    protected function detectType(mixed $value): string
    {
        return match(true) {
            is_array($value) && array_is_list($value) => 'indexed_array',
            is_array($value) => 'associative_array',
            is_string($value) => 'string',
            is_int($value) => 'int',
            is_float($value) => 'float',
            is_bool($value) => 'bool',
            is_object($value) => 'object',
            default => 'mixed'
        };
    }

    /**
     * Applies type-specific coercion based on current type context.
     *
     * @param mixed $value The value to coerce
     * @return mixed The coerced value
     */
    protected function coerceForCurrentType(mixed $value): mixed
    {
        return match($this->getCurrentType()) {
            'indexed_array' => $this->coerceToIndexedArray($value),
            'associative_array' => $this->coerceToAssociativeArray($value),
            'string' => $value, // No coercion needed
            'int' => $value,    // No coercion needed
            'float' => $value,  // No coercion needed
            'bool' => $value,   // No coercion needed
            'object' => $value, // No coercion needed
            default => $value
        };
    }

    /**
     * Coerces value to indexed array (reindexes if necessary).
     *
     * @param mixed $value The value to coerce
     * @return mixed The coerced value
     */
    protected function coerceToIndexedArray(mixed $value): mixed
    {
        if (!is_array($value)) return $value;
        return array_is_list($value) ? $value : array_values($value);
    }

    /**
     * Coerces value to associative array (preserves keys).
     *
     * @param mixed $value The value to coerce
     * @return mixed The coerced value
     */
    protected function coerceToAssociativeArray(mixed $value): mixed
    {
        if (!is_array($value)) return $value;
        return $value; // Preserve keys - no reindexing!
    }
}
