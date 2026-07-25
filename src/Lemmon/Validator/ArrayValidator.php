<?php

declare(strict_types=1);

namespace Lemmon\Validator;

class ArrayValidator extends FieldValidator
{
    private ?FieldValidator $itemValidator = null;
    private bool $filterEmpty = false;
    private bool $coerceAll = false;

    /**
     * @inheritDoc
     */
    protected function getValidatorType(): string
    {
        return 'indexed_array';
    }

    /**
     * Sets the validator for array items.
     *
     * @param FieldValidator $validator The validator for each array item.
     * @return $this
     */
    public function items(FieldValidator $validator): self
    {
        $this->itemValidator = $this->coerceAll ? $validator->clone()->coerceAll() : $validator->clone();
        return $this;
    }

    /**
     * Removes empty values (empty strings and null) from the array and reindexes it.
     * Maintains the indexed array structure that ArrayValidator expects.
     *
     * @return $this
     */
    public function filterEmpty(): self
    {
        $this->filterEmpty = true;
        return $this;
    }

    /**
     * Validates that the array is not empty.
     *
     * @param null|string $message Custom error message
     * @return $this
     */
    public function notEmpty(?string $message = null): static
    {
        return $this->satisfies(
            static fn(array $value): bool => count($value) >= 1,
            $message ?? 'Value must not be empty',
            ValidationCode::NOT_EMPTY,
        );
    }

    /**
     * Validates that the array has at least the specified number of items.
     *
     * @param int $min Minimum number of items required
     * @param null|string $message Custom error message
     * @return $this
     */
    public function minItems(int $min, ?string $message = null): static
    {
        if ($min < 0) {
            throw new \InvalidArgumentException('Minimum item count cannot be negative');
        }

        return $this->satisfies(
            static fn(array $value): bool => count($value) >= $min,
            $message ?? "Value must contain at least {$min} items",
            ValidationCode::ARRAY_TOO_FEW_ITEMS,
            ['min' => $min],
        );
    }

    /**
     * Validates that the array has at most the specified number of items.
     *
     * @param int $max Maximum number of items allowed
     * @param null|string $message Custom error message
     * @return $this
     */
    public function maxItems(int $max, ?string $message = null): static
    {
        if ($max < 0) {
            throw new \InvalidArgumentException('Maximum item count cannot be negative');
        }

        return $this->satisfies(
            static fn(array $value): bool => count($value) <= $max,
            $message ?? "Value must contain at most {$max} items",
            ValidationCode::ARRAY_TOO_MANY_ITEMS,
            ['max' => $max],
        );
    }

    /**
     * Validates that a specific field value is unique across all array items.
     * Items are expected to be associative arrays or objects with the specified field.
     * Items where the field is missing or null are skipped.
     *
     * Produces field-level structured error paths (e.g., `symlinks.2.destination`).
     *
     * @param string $fieldName The field name to check for uniqueness
     * @param null|string $message Custom error message for duplicate values
     * @return $this
     */
    public function uniqueField(string $fieldName, ?string $message = null): static
    {
        return $this->satisfies(
            static function (array $value) use ($fieldName, $message): bool {
                $seen = [];

                foreach ($value as $index => $item) {
                    $fieldValue = match (true) {
                        is_array($item) => $item[$fieldName] ?? null,
                        is_object($item) => $item->$fieldName ?? null,
                        default => null,
                    };

                    if ($fieldValue === null) {
                        continue;
                    }

                    // Most field values are scalars, for which serialize() gives a stable
                    // value-equality key (uniqueField is meant for scalar fields). Closures and
                    // some internal objects throw on serialize() -- and that throw must not escape
                    // uniqueField, which runs inside tryValidate() -- so fall back to identity: a
                    // repeated object instance still collides (spl_object_id), while a non-object we
                    // cannot value-compare is keyed by position and so is always treated as unique.
                    // Resources are a known gap: serialize() does not throw for them but maps every
                    // resource to "i:0;", so distinct handles collide (see ROADMAP known limitations).
                    try {
                        $serialized = serialize($fieldValue);
                    } catch (\Throwable) {
                        $serialized = is_object($fieldValue)
                            ? 'object#' . spl_object_id($fieldValue)
                            : 'item#' . $index;
                    }
                    $seen[$serialized] ??= ['value' => $fieldValue, 'indices' => []];
                    $seen[$serialized]['indices'][] = $index;
                }

                /** @var array<ValidationError> $errors */
                $errors = [];

                foreach ($seen as $entry) {
                    if (count($entry['indices']) <= 1) {
                        continue;
                    }

                    $displayValue = ValidationError::stringify($entry['value']);

                    foreach ($entry['indices'] as $idx) {
                        $others = array_values(array_filter(
                            $entry['indices'],
                            static fn($i) => $i !== $idx,
                        ));

                        $params = ['field' => $fieldName, 'value' => $entry['value'], 'others' => $others];

                        // A custom message may use {field}/{value}/{others} placeholders, interpolated
                        // like every other built-in rule. The default message already embeds the value
                        // (which could itself contain brace characters), so it is left untouched.
                        $resolvedMessage = $message !== null
                            ? ValidationError::interpolate($message, $params)
                            : match (count($others)) {
                                1 => "Value '{$displayValue}' is not unique (also at index {$others[0]})",
                                default => "Value '{$displayValue}' is not unique (also at indices "
                                    . implode(', ', array_map(
                                        static fn(int|string $other): string => (string) $other,
                                        $others,
                                    ))
                                    . ')',
                            };

                        $errors[] = new ValidationError(
                            [$idx, $fieldName],
                            ValidationCode::NOT_UNIQUE,
                            $resolvedMessage,
                            $params,
                        );
                    }
                }

                if ($errors !== []) {
                    throw new ValidationException($errors);
                }

                return true;
            },
        );
    }

    /**
     * Validates that the array contains a specific value or an item matching the provided validator.
     *
     * @param mixed $valueOrValidator Either a specific value to find, or a FieldValidator to match against items
     * @param null|string $message Custom error message
     * @return $this
     */
    public function contains(mixed $valueOrValidator, ?string $message = null): static
    {
        $message ??= 'Value must contain the required item';

        if ($valueOrValidator instanceof FieldValidator) {
            $valueOrValidator = $valueOrValidator->clone();

            $this->addValidationStep(
                self::buildContainsRule($valueOrValidator),
                $message,
                static fn(): \Closure => self::buildValidationOperation(
                    self::buildContainsRule($valueOrValidator->clone()),
                    $message,
                    ValidationCode::CONTAINS,
                ),
                ValidationCode::CONTAINS,
            );

            return $this;
        }

        $this->addValidationStep(
            self::buildContainsRule($valueOrValidator),
            $message,
            null,
            ValidationCode::CONTAINS,
            ['value' => $valueOrValidator],
        );

        return $this;
    }

    private static function buildContainsRule(mixed $valueOrValidator): \Closure
    {
        return static function (array $value) use ($valueOrValidator): bool {
            if ($valueOrValidator instanceof FieldValidator) {
                foreach ($value as $item) {
                    [$valid] = $valueOrValidator->tryValidate($item);
                    if ($valid) {
                        return true;
                    }
                }

                return false;
            }

            return in_array($valueOrValidator, $value, true);
        };
    }

    /**
     * @inheritDoc
     */
    protected function coerceValue(mixed $value): mixed
    {
        // Let null pass through to be handled by required/default logic
        if (is_null($value)) {
            return $value;
        }

        // If it's already an array, convert associative to indexed
        if (is_array($value)) {
            // If it's already a list, return as-is
            if (array_is_list($value)) {
                return $value;
            }
            // Convert associative array to indexed array
            return array_values($value);
        }

        // Form-safe: empty string means "no value provided"; other scalars wrap to single-item array
        if (is_scalar($value)) {
            return $value === '' ? null : [$value];
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    protected function validateType(mixed $value, string $key): mixed
    {
        if (!is_array($value)) {
            throw self::typeError('Value must be an array', 'array');
        }

        // Check if it's a list (indexed array starting from 0)
        if (!array_is_list($value)) {
            throw self::typeError('Value must be an indexed array (list)', 'indexed_array');
        }

        // Apply filterEmpty transformation if enabled
        if ($this->filterEmpty) {
            $value = array_values(array_filter(
                $value,
                static fn($item) => $item !== '' && $item !== null,
            ));
        }

        // If item validator is set, validate each item
        if ($this->itemValidator !== null) {
            $validatedItems = [];
            /** @var array<ValidationError> $errors */
            $errors = [];

            foreach ($value as $index => $item) {
                [$valid, $validatedItem, $itemErrors] = $this->itemValidator->tryValidate(
                    $item,
                    (string) $index,
                    $value,
                );

                if (!$valid) {
                    foreach ($itemErrors as $error) {
                        $errors[] = $error->withPathPrefix($index);
                    }
                    continue;
                }

                $validatedItems[] = $validatedItem;
            }

            if ($errors !== []) {
                throw new ValidationException($errors);
            }

            return $validatedItems;
        }

        return $value;
    }

    public function coerceAll(): static
    {
        if ($this->coerceAll) {
            return $this;
        }
        $this->coerce = true;
        $this->coerceAll = true;
        if ($this->itemValidator !== null) {
            $this->itemValidator = $this->itemValidator->coerceAll();
        }
        return $this;
    }

    public function __clone()
    {
        parent::__clone();

        if ($this->itemValidator !== null) {
            $this->itemValidator = $this->itemValidator->clone();
        }
    }
}
