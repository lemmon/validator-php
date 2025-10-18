<?php

namespace Lemmon;

abstract class FieldValidator
{
    protected mixed $default = null;
    protected bool $hasDefault = false;
    protected bool $coerce = false;
    protected bool $required = false;
    protected ?string $requiredMessage = null;

    /**
     * @var array<array{type: PipelineType, operation: callable}>
     */
    protected array $pipeline = [];
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
        $this->required = true;
        $this->requiredMessage = $message ?? 'Value is required';
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
        $this->pipeline[] = [
            'type' => PipelineType::TRANSFORMATION,
            'operation' => function ($value) {
                return (($value === '') || (is_array($value) && empty($value))) ? null : $value;
            }
        ];
        return $this;
    }

    /**
     * Adds a custom validation rule with an optional error message.
     *
     * @param callable|FieldValidator $validation The validation function or validator instance.
     * @param ?string $message Optional custom error message. If not provided, a generic message is used.
     * @return $this
     */
    public function satisfies(callable|FieldValidator $validation, ?string $message = null): self
    {
        if ($validation instanceof FieldValidator) {
            // Convert FieldValidator to callable
            $rule = function ($value, $key = null, $input = null) use ($validation) {
                [$valid, , ] = $validation->tryValidate($value, $key, $input);
                return $valid;
            };
        } else {
            $rule = $validation;
        }

        $this->pipeline[] = [
            'type' => PipelineType::VALIDATION,
            'operation' => function ($value, $key = null, $input = null) use ($rule, $message) {
                if (!$rule($value, $key, $input)) {
                    throw new ValidationException([$message ?? 'Custom validation failed']);
                }
                return $value;
            }
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
     * Validates that the value satisfies ALL of the provided validators or callables.
     *
     * @param array<FieldValidator|callable> $validations Array of validators/callables that must all pass.
     * @param ?string $message Custom error message.
     * @return $this
     */
    public function satisfiesAll(array $validations, ?string $message = null): self
    {
        return $this->satisfies(
            function ($value, $key = null, $input = null) use ($validations) {
                foreach ($validations as $validation) {
                    if ($validation instanceof FieldValidator) {
                        [$valid, , ] = $validation->tryValidate($value, $key, $input);
                        if (!$valid) {
                            return false;
                        }
                    } else {
                        if (!$validation($value, $key, $input)) {
                            return false;
                        }
                    }
                }
                return true;
            },
            $message ?? 'Value must satisfy all validation rules'
        );
    }

    /**
     * @deprecated Use satisfiesAll() instead. Will be removed in v1.0.0.
     * @param array<FieldValidator|callable> $validators
     */
    public function allOf(array $validators, ?string $message = null): self
    {
        return $this->satisfiesAll($validators, $message);
    }

    /**
     * Validates that the value satisfies ANY of the provided validators or callables.
     *
     * @param array<FieldValidator|callable> $validations Array of validators/callables, at least one must pass.
     * @param ?string $message Custom error message.
     * @return $this
     */
    public function satisfiesAny(array $validations, ?string $message = null): self
    {
        return $this->satisfies(
            function ($value, $key = null, $input = null) use ($validations) {
                foreach ($validations as $validation) {
                    if ($validation instanceof FieldValidator) {
                        [$valid, , ] = $validation->tryValidate($value, $key, $input);
                        if ($valid) {
                            return true;
                        }
                    } else {
                        if ($validation($value, $key, $input)) {
                            return true;
                        }
                    }
                }
                return false;
            },
            $message ?? 'Value must satisfy at least one validation rule'
        );
    }

    /**
     * @deprecated Use satisfiesAny() instead. Will be removed in v1.0.0.
     * @param array<FieldValidator|callable> $validators
     */
    public function anyOf(array $validators, ?string $message = null): self
    {
        return $this->satisfiesAny($validators, $message);
    }

    /**
     * Validates that the value satisfies NONE of the provided validators or callables.
     *
     * @param array<FieldValidator|callable> $validations Array of validators/callables that must all fail.
     * @param ?string $message Custom error message.
     * @return $this
     */
    public function satisfiesNone(array $validations, ?string $message = null): self
    {
        return $this->satisfies(
            function ($value, $key = null, $input = null) use ($validations) {
                foreach ($validations as $validation) {
                    if ($validation instanceof FieldValidator) {
                        [$valid, , ] = $validation->tryValidate($value, $key, $input);
                        if ($valid) {
                            return false; // If any validation passes, satisfiesNone fails
                        }
                    } else {
                        if ($validation($value, $key, $input)) {
                            return false; // If any validation passes, satisfiesNone fails
                        }
                    }
                }
                return true; // All validations failed, so satisfiesNone passes
            },
            $message ?? 'Value must not satisfy any of the validation rules'
        );
    }

    /**
     * @deprecated Use satisfiesNone() instead. Will be removed in v1.0.0.
     */
    public function not(FieldValidator $validator, ?string $message = null): self
    {
        return $this->satisfiesNone([$validator], $message ?? 'Value must not satisfy the validation rule');
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
        $this->pipeline[] = [
            'type' => PipelineType::TRANSFORMATION,
            'operation' => function ($value) use ($transformer) {
                $result = $transformer($value);

                // Update current type context based on result
                $this->currentType = $this->detectType($result);

                return $result; // No coercion - transform can change type
            }
        ];
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
            $this->pipeline[] = [
                'type' => PipelineType::TRANSFORMATION,
                'operation' => function ($value) use ($transformer) {
                    $result = $transformer($value);

                    // Apply type-specific coercion based on current type context
                    return $this->coerceForCurrentType($result);
                }
            ];
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
        // Handle initial null values - only return early if no pipeline, no default, and not required
        if (is_null($value) && empty($this->pipeline) && !$this->required) {
            return $this->hasDefault ? [true, $this->default, null] : [true, null, null];
        }

        $value = $this->coerce ? $this->coerceValue($value) : $value;

        // Handle null values after coercion - only return early if no pipeline, no default, and not required
        if (is_null($value) && empty($this->pipeline) && !$this->required) {
            return $this->hasDefault ? [true, $this->default, null] : [true, null, null];
        }

        try {
            // Check required first for null values
            if ($this->required && is_null($value)) {
                throw new ValidationException([$this->requiredMessage]);
            }

            // Skip type validation for null values if we have pipeline (let pipeline handle it)
            $processedValue = is_null($value) && !empty($this->pipeline) ? $value : $this->validateType($value, $key);

            // Execute the unified pipeline with smart null handling
            foreach ($this->pipeline as $step) {
                $operation = $step['operation'];
                $type = $step['type'];

                // Smart null handling: validations skip null (already checked required), transformations always execute
                if (is_null($processedValue) && $type === PipelineType::VALIDATION) {
                    continue; // Skip validation for null values (required already checked)
                }

                $processedValue = $operation($processedValue, $key, $input);

                // Check required again if value became null during transformations
                if ($this->required && is_null($processedValue)) {
                    throw new ValidationException([$this->requiredMessage]);
                }
            }

            // Apply default if final result is null
            if (is_null($processedValue) && $this->hasDefault) {
                $processedValue = $this->default;
            }

            return [true, $processedValue, null];
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
