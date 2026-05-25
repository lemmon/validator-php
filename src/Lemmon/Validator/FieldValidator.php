<?php

declare(strict_types=1);

namespace Lemmon\Validator;

abstract class FieldValidator
{
    protected mixed $default = null;
    protected ?\Closure $defaultFactory = null;
    protected bool $hasDefault = false;
    protected bool $coerce = false;
    protected bool $required = false;
    protected ?string $requiredMessage = null;
    protected ?string $outputKey = null;

    /**
     * Creates a deep copy of the validator, including pipeline closures bound to the clone.
     *
     * @return static
     */
    public function clone(): static
    {
        return clone $this;
    }

    /**
     * @var array<PipelineStep>
     */
    protected array $pipeline = [];

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
        $this->defaultFactory = null;
        $this->hasDefault = true;
        return $this;
    }

    /**
     * Sets a default factory that is invoked whenever validation resolves to null.
     *
     * Use this whenever the default is an object that should not be shared across validation
     * runs. {@see default()} stores the value as-is, so object defaults are returned by handle;
     * this method invokes the factory on each run, guaranteeing a fresh instance.
     * Prefer static closures so the factory does not implicitly capture the caller's `$this`.
     *
     * @param callable(): mixed $factory
     * @return $this
     */
    public function defaultUsing(callable $factory): self
    {
        $this->default = null;
        $this->defaultFactory = \Closure::fromCallable($factory);
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
     * Enables coercion on this validator and recursively on any nested validators.
     *
     * On leaf validators this is identical to {@see coerce()}. Schema validators and
     * {@see ArrayValidator} override this to propagate coercion to their children
     * (schema fields, array items, nested schemas).
     *
     * Does not propagate into assertion operands -- validators passed to
     * {@see satisfies()}, {@see satisfiesAll()}, {@see satisfiesAny()},
     * {@see satisfiesNone()}, or {@see ArrayValidator::contains()}. Call
     * {@see coerce()} on those validators individually when needed.
     *
     * @return $this
     */
    public function coerceAll(): static
    {
        $this->coerce = true;
        return $this;
    }

    /**
     * Sets the output key for schema fields. When used in AssociativeValidator or ObjectValidator,
     * the validated value is stored under this key instead of the input field key.
     *
     * @param string $key The key to use in the output structure
     * @return $this
     */
    public function outputKey(string $key): self
    {
        $this->outputKey = $key;
        return $this;
    }

    /**
     * Enables nullification of empty values (empty string, empty array) to null.
     *
     * @return $this
     */
    public function nullifyEmpty(): self
    {
        $this->pipeline[] = new PipelineStep(
            type: PipelineType::TRANSFORMATION,
            operation: static fn($value) => $value === '' || is_array($value) && $value === [] ? null : $value,
            skipNull: true,
        );
        return $this;
    }

    /**
     * Adds a custom validation rule with an optional error message.
     *
     * @param callable|FieldValidator $validation The validation function or validator instance.
     * @param ?string $message Optional custom error message. If not provided, a generic message is used.
     *                         May contain `{name}` placeholders substituted from $params.
     * @param ?string $code Optional structured error code (defaults to {@see ValidationCode::CUSTOM}).
     * @param array<string, mixed> $params Optional params recorded on the error and used for placeholder
     *                                     substitution in the message.
     * @return $this
     */
    public function satisfies(
        callable|FieldValidator $validation,
        ?string $message = null,
        ?string $code = null,
        array $params = [],
    ): self {
        $code ??= ValidationCode::CUSTOM;

        if ($validation instanceof FieldValidator) {
            $validation = $validation->clone();

            $this->addValidationStep(
                self::buildFieldValidatorRule($validation),
                $message,
                static fn(): \Closure => self::buildValidationOperation(
                    self::buildFieldValidatorRule($validation->clone()),
                    $message,
                    $code,
                    $params,
                ),
                $code,
                $params,
            );

            return $this;
        }

        $this->addValidationStep($validation, $message, null, $code, $params);

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
     * @param array<FieldValidator|callable> $validations
     * @return array<FieldValidator|callable>
     */
    private static function cloneValidatorOperands(array $validations): array
    {
        return array_map(
            static fn($v) => $v instanceof FieldValidator ? $v->clone() : $v,
            $validations,
        );
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
        $message ??= 'Value must satisfy all validation rules';
        $validations = self::cloneValidatorOperands($validations);

        $this->addValidationStep(
            self::buildAllRule($validations),
            $message,
            self::hasFieldValidatorOperands($validations)
                ? static fn(): \Closure => self::buildValidationOperation(
                    self::buildAllRule(self::cloneValidatorOperands($validations)),
                    $message,
                    ValidationCode::ALL_OF,
                )
                : null,
            ValidationCode::ALL_OF,
        );

        return $this;
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
        $message ??= 'Value must satisfy at least one validation rule';
        $validations = self::cloneValidatorOperands($validations);

        $this->addValidationStep(
            self::buildAnyRule($validations),
            $message,
            self::hasFieldValidatorOperands($validations)
                ? static fn(): \Closure => self::buildValidationOperation(
                    self::buildAnyRule(self::cloneValidatorOperands($validations)),
                    $message,
                    ValidationCode::ANY_OF,
                )
                : null,
            ValidationCode::ANY_OF,
        );

        return $this;
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
        $message ??= 'Value must not satisfy any of the validation rules';
        $validations = self::cloneValidatorOperands($validations);

        $this->addValidationStep(
            self::buildNoneRule($validations),
            $message,
            self::hasFieldValidatorOperands($validations)
                ? static fn(): \Closure => self::buildValidationOperation(
                    self::buildNoneRule(self::cloneValidatorOperands($validations)),
                    $message,
                    ValidationCode::NONE_OF,
                )
                : null,
            ValidationCode::NONE_OF,
        );

        return $this;
    }

    /**
     * @param array<FieldValidator|callable> $validations
     */
    private static function hasFieldValidatorOperands(array $validations): bool
    {
        foreach ($validations as $validation) {
            if ($validation instanceof FieldValidator) {
                return true;
            }
        }

        return false;
    }

    private static function buildFieldValidatorRule(FieldValidator $validation): \Closure
    {
        return static function ($value, $key = null, $input = null) use ($validation): bool {
            [$valid] = $validation->tryValidate($value, $key, $input);
            return $valid;
        };
    }

    /**
     * @param array<FieldValidator|callable> $validations
     */
    private static function buildAllRule(array $validations): \Closure
    {
        return static function ($value, $key = null, $input = null) use ($validations): bool {
            foreach ($validations as $validation) {
                if ($validation instanceof FieldValidator) {
                    [$valid] = $validation->tryValidate($value, $key, $input);
                    if (!$valid) {
                        return false;
                    }
                    continue;
                }

                if (!$validation($value, $key, $input)) {
                    return false;
                }
            }

            return true;
        };
    }

    /**
     * @param array<FieldValidator|callable> $validations
     */
    private static function buildAnyRule(array $validations): \Closure
    {
        return static function ($value, $key = null, $input = null) use ($validations): bool {
            foreach ($validations as $validation) {
                if ($validation instanceof FieldValidator) {
                    [$valid] = $validation->tryValidate($value, $key, $input);
                    if ($valid) {
                        return true;
                    }
                    continue;
                }

                if ($validation($value, $key, $input)) {
                    return true;
                }
            }

            return false;
        };
    }

    /**
     * @param array<FieldValidator|callable> $validations
     */
    private static function buildNoneRule(array $validations): \Closure
    {
        return static function ($value, $key = null, $input = null) use ($validations): bool {
            foreach ($validations as $validation) {
                if ($validation instanceof FieldValidator) {
                    [$valid] = $validation->tryValidate($value, $key, $input);
                    if ($valid) {
                        return false;
                    }
                    continue;
                }

                if ($validation($value, $key, $input)) {
                    return false;
                }
            }

            return true;
        };
    }

    /**
     * Wraps a boolean rule callable in a pipeline-compatible operation that throws on failure.
     */
    /**
     * @param array<string, mixed> $params
     */
    protected static function buildValidationOperation(
        callable $rule,
        ?string $message = null,
        string $code = ValidationCode::CUSTOM,
        array $params = [],
    ): \Closure {
        return static function ($value, ?PipelineContext $context = null, $key = null, $input = null) use (
            $rule,
            $message,
            $code,
            $params,
        ) {
            if (!$rule($value, $key, $input)) {
                throw new ValidationException([
                    new ValidationError(
                        '',
                        $code,
                        ValidationError::interpolate($message ?? 'Custom validation failed', $params),
                        $params,
                    ),
                ]);
            }

            return $value;
        };
    }

    /**
     * Appends a validation step to the pipeline.
     *
     * When $rule captures a FieldValidator operand, the caller must clone that operand
     * before building $rule *and* supply a $rebuildOperation that produces a fresh
     * operation from a new clone, so that {@see __clone()} can isolate pipeline state.
     *
     * @param array<string, mixed> $params
     */
    protected function addValidationStep(
        callable $rule,
        ?string $message = null,
        ?\Closure $rebuildOperation = null,
        string $code = ValidationCode::CUSTOM,
        array $params = [],
    ): void {
        $this->pipeline[] = new PipelineStep(
            type: PipelineType::VALIDATION,
            operation: self::buildValidationOperation($rule, $message, $code, $params),
            skipNull: true,
            rebuildOperation: $rebuildOperation,
        );
    }

    /**
     * Builds a root-level type-mismatch error ({@see ValidationCode::INVALID_TYPE}).
     *
     * @param string $expected The expected type name (e.g. 'string', 'indexed_array').
     */
    protected static function typeError(string $message, string $expected): ValidationException
    {
        return new ValidationException([
            new ValidationError('', ValidationCode::INVALID_TYPE, $message, ['expected' => $expected]),
        ]);
    }

    /**
     * @deprecated Use satisfiesNone() instead. Will be removed in v1.0.0.
     */
    public function not(FieldValidator $validator, ?string $message = null): self
    {
        return $this->satisfiesNone(
            [$validator],
            $message ?? 'Value must not satisfy the validation rule',
        );
    }

    /**
     * Restricts the value to exactly one allowed constant.
     * Uses strict comparison (===).
     *
     * @param mixed $value The single allowed value.
     * @param ?string $message Optional custom error message.
     * @return $this
     */
    public function const(mixed $value, ?string $message = null): self
    {
        return $this->satisfies(
            static fn($v) => $v === $value,
            $message ?? 'Value must be ' . (is_scalar($value) ? var_export($value, true) : json_encode($value)),
            ValidationCode::CONST,
            ['expected' => $value],
        );
    }

    /**
     * Restricts the value to a PHP enum case.
     *
     * For BackedEnum, the value must be int or string matching one of the backed values.
     * For UnitEnum (non-backed), the value must be an instance of the enum or a string
     * equal to one of the case names.
     *
     * @param string $enumClass Fully qualified enum class name (e.g. StatusEnum::class).
     * @param ?string $message Optional custom error message.
     * @return $this
     * @throws \InvalidArgumentException If $enumClass is not an actual enum (e.g. an interface or plain class).
     */
    public function enum(string $enumClass, ?string $message = null): self
    {
        if (!enum_exists($enumClass)) {
            throw new \InvalidArgumentException(
                sprintf('Class must be a BackedEnum or UnitEnum, got: %s', $enumClass),
            );
        }

        if (is_subclass_of($enumClass, \BackedEnum::class, true)) {
            $allowedValues = array_map(static fn(\BackedEnum $c) => $c->value, $enumClass::cases());
            $allowed = implode(', ', array_map(static fn($v) => var_export($v, true), $allowedValues));

            return $this->satisfies(
                static function ($v) use ($enumClass): bool {
                    if (!is_int($v) && !is_string($v)) {
                        return false;
                    }

                    return $enumClass::tryFrom($v) !== null;
                },
                $message ?? 'Value must be one of: ' . $allowed,
                ValidationCode::ENUM,
                ['allowed' => $allowedValues],
            );
        }

        if (is_subclass_of($enumClass, \UnitEnum::class, true)) {
            $allowedNames = array_map(static fn(\UnitEnum $c) => $c->name, $enumClass::cases());
            $allowed = implode(', ', array_map(static fn($n) => var_export($n, true), $allowedNames));

            return $this->satisfies(
                static function ($v) use ($enumClass): bool {
                    if ($v instanceof $enumClass) {
                        return true;
                    }

                    if (!is_string($v)) {
                        return false;
                    }

                    foreach ($enumClass::cases() as $case) {
                        if ($case->name === $v) {
                            return true;
                        }
                    }

                    return false;
                },
                $message ?? 'Value must be one of: ' . $allowed,
                ValidationCode::ENUM,
                ['allowed' => $allowedNames],
            );
        }

        throw new \InvalidArgumentException(
            sprintf('Class must be a BackedEnum or UnitEnum, got: %s', $enumClass),
        );
    }

    /**
     * Adds a transformation function to be applied after successful validation.
     * Can change the type - subsequent operations work with the new type.
     *
     * @param callable $transformer The transformation function that receives the validated value.
     * @param bool $skipNull Whether to skip null values (default: true). Set to false to process null values.
     * @return $this
     */
    public function transform(callable $transformer, bool $skipNull = true): self
    {
        $this->pipeline[] = new PipelineStep(
            type: PipelineType::TRANSFORMATION,
            operation: static function ($value, PipelineContext $context) use ($transformer) {
                $result = $transformer($value);

                // Record the new type so later pipe steps coerce against it
                $context->setTypeFrom($result);

                return $result; // No coercion - transform can change type
            },
            skipNull: $skipNull,
        );
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
            $this->pipeline[] = new PipelineStep(
                type: PipelineType::TRANSFORMATION,
                operation: static fn($value, PipelineContext $context) => $context->coerce($transformer($value)),
                skipNull: true,
            );
        }
        return $this;
    }

    /**
     * Validates the given value against the defined rules.
     *
     * @param mixed $value The value to validate.
     * @param string $key The key of the field being validated.
     * @param mixed $input The entire input payload (array or object).
     * @return mixed The validated and potentially coerced value.
     * @throws ValidationException If validation fails.
     */
    public function validate(mixed $value, string $key = '', mixed $input = null): mixed
    {
        [$valid, $data, $errors] = $this->tryValidate($value, $key, $input);
        if (!$valid) {
            throw new ValidationException(
                $errors ?? [new ValidationError('', ValidationCode::CUSTOM, 'Validation failed')],
            );
        }
        return $data;
    }

    /**
     * Tries to validate the given value and returns a result tuple.
     *
     * @param mixed $value The value to validate.
     * @param string $key The key of the field being validated.
     * @param mixed|null $input The entire input payload (array or object).
     * @return array{bool, mixed, array<ValidationError>|null} A tuple containing:
     *                                                 - bool: true if validation is successful, false otherwise.
     *                                                 - mixed: The validated and potentially coerced value on success, or the (possibly coerced) input value on failure.
     *                                                 - array|null: A list of structured {@see ValidationError} objects on failure, or null on success.
     */
    public function tryValidate(mixed $value, string $key = '', mixed $input = null): array
    {
        // Coerce input
        if ($this->coerce) {
            $value = $this->coerceValue($value);
        }

        try {
            // Type validation (skip null -- default/required will handle it)
            $processedValue = is_null($value) ? $value : $this->validateType($value, $key);

            // Per-run type context, seeded with this validator's type and discarded when the run ends
            $context = new PipelineContext($this->getValidatorType());

            // Execute pipeline
            foreach ($this->pipeline as $step) {
                if (is_null($processedValue) && $step->skipNull) {
                    continue;
                }
                $processedValue = ($step->operation)($processedValue, $context, $key, $input);
            }

            // Default -- last resort fallback for null
            if (is_null($processedValue) && $this->hasDefault) {
                $processedValue = $this->resolveDefaultValue();
            }

            // Required -- single check, always last
            if ($this->required && is_null($processedValue)) {
                throw new ValidationException([
                    new ValidationError('', ValidationCode::REQUIRED, $this->requiredMessage ?? 'Value is required'),
                ]);
            }

            return [true, $processedValue, null];
        } catch (ValidationException $e) {
            return [false, $value, $e->getStructuredErrors()];
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

    private function resolveDefaultValue(): mixed
    {
        if ($this->defaultFactory instanceof \Closure) {
            return ($this->defaultFactory)();
        }

        return $this->default;
    }

    public function __clone()
    {
        // Operations are static closures with no bound state, so steps can be shared as-is --
        // except those capturing a FieldValidator operand, whose operation is rebuilt from a
        // fresh clone so the operand's pipeline state stays isolated from the original.
        $rebuiltPipeline = [];
        foreach ($this->pipeline as $step) {
            $rebuiltPipeline[] = $step->rebuildOperation instanceof \Closure
                ? $step->withOperation(($step->rebuildOperation)())
                : $step;
        }

        $this->pipeline = $rebuiltPipeline;
    }
}
