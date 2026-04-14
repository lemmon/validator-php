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
        return $this->minItems(1, $message ?? 'Value must not be empty');
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
        return $this->satisfies(
            static fn($value, $key = null, $input = null) => count($value) >= $min,
            $message ?? "Value must contain at least {$min} items",
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
        return $this->satisfies(
            static fn($value, $key = null, $input = null) => count($value) <= $max,
            $message ?? "Value must contain at most {$max} items",
        );
    }

    /**
     * Validates that a specific field value is unique across all array items.
     * Items are expected to be associative arrays or objects with the specified field.
     * Items where the field is missing or null are skipped.
     *
     * Produces field-level error paths (e.g., `symlinks.2.destination`) by structuring
     * errors as `[index => [fieldName => [message]]]`.
     *
     * @param string $fieldName The field name to check for uniqueness
     * @param null|string $message Custom error message for duplicate values
     * @return $this
     */
    public function uniqueField(string $fieldName, ?string $message = null): static
    {
        return $this->satisfies(
            static function ($value, $key = null, $input = null) use ($fieldName, $message) {
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

                    $serialized = serialize($fieldValue);
                    $seen[$serialized] ??= ['value' => $fieldValue, 'indices' => []];
                    $seen[$serialized]['indices'][] = $index;
                }

                $errors = [];

                foreach ($seen as $entry) {
                    if (count($entry['indices']) <= 1) {
                        continue;
                    }

                    $displayValue = (string) $entry['value'];
                    if (!is_scalar($entry['value'])) {
                        $encoded = json_encode($entry['value']);
                        $displayValue = $encoded !== false ? $encoded : '(complex value)';
                    }

                    foreach ($entry['indices'] as $idx) {
                        $others = array_values(array_filter(
                            $entry['indices'],
                            static fn($i) => $i !== $idx,
                        ));

                        $defaultMessage = $message ?? match (count($others)) {
                            1 => "Value '{$displayValue}' is not unique (also at index {$others[0]})",
                            default => "Value '{$displayValue}' is not unique (also at indices "
                                . implode(', ', $others)
                                . ')',
                        };

                        $errors[$idx] = [
                            $fieldName => [$defaultMessage],
                        ];
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
                ),
            );

            return $this;
        }

        $this->addValidationStep(
            self::buildContainsRule($valueOrValidator),
            $message,
        );

        return $this;
    }

    private static function buildContainsRule(mixed $valueOrValidator): \Closure
    {
        return static function ($value, $key = null, $input = null) use ($valueOrValidator): bool {
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

        // Coerce scalar values to array: empty string to empty array, others to single-item array
        if (is_scalar($value)) {
            return $value === '' ? [] : [$value];
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    protected function validateType(mixed $value, string $key): mixed
    {
        if (!is_array($value)) {
            throw new ValidationException(['Value must be an array']);
        }

        // Check if it's a list (indexed array starting from 0)
        if (!array_is_list($value)) {
            throw new ValidationException(['Value must be an indexed array (list)']);
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
            $errors = [];

            foreach ($value as $index => $item) {
                $itemKey = ($key ? $key . '.' : '') . $index;
                [$valid, $validatedItem, $itemErrors] = $this->itemValidator->tryValidate(
                    $item,
                    (string) $index,
                    $value,
                );

                if (!$valid) {
                    // Wrap item errors under the index key
                    $errors[$index] = $itemErrors;
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
