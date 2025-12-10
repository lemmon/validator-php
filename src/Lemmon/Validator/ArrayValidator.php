<?php

declare(strict_types=1);

namespace Lemmon\Validator;

class ArrayValidator extends FieldValidator
{
    private null|FieldValidator $itemValidator = null;
    private bool $filterEmpty = false;

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
        $this->itemValidator = $validator;
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
     * Validates that the array has at least the specified number of items.
     *
     * @param int $min Minimum number of items required
     * @param null|string $message Custom error message
     * @return $this
     */
    public function minItems(int $min, null|string $message = null): static
    {
        return $this->satisfies(
            static fn ($value, $key = null, $input = null) => count($value) >= $min,
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
    public function maxItems(int $max, null|string $message = null): static
    {
        return $this->satisfies(
            static fn ($value, $key = null, $input = null) => count($value) <= $max,
            $message ?? "Value must contain at most {$max} items",
        );
    }

    /**
     * Validates that the array contains a specific value or an item matching the provided validator.
     *
     * @param mixed $valueOrValidator Either a specific value to find, or a FieldValidator to match against items
     * @param null|string $message Custom error message
     * @return $this
     */
    public function contains(mixed $valueOrValidator, null|string $message = null): static
    {
        return $this->satisfies(
            static function ($value, $key = null, $input = null) use ($valueOrValidator) {
                if ($valueOrValidator instanceof FieldValidator) {
                    // Check if any item matches the validator
                    foreach ($value as $item) {
                        [$valid] = $valueOrValidator->tryValidate($item);
                        if ($valid) {
                            return true;
                        }
                    }
                    return false;
                }

                // Check if array contains the specific value (strict comparison)
                return in_array($valueOrValidator, $value, true);
            },
            $message ?? 'Value must contain the required item',
        );
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
                static fn ($item) => $item !== '' && $item !== null,
            ));
        }

        // If item validator is set, validate each item
        if ($this->itemValidator !== null) {
            $validatedItems = [];
            foreach ($value as $index => $item) {
                try {
                    $itemKey = ($key ? $key . '.' : '') . $index;
                    $validatedItems[] = $this->itemValidator->validate($item, $itemKey, []);
                } catch (ValidationException $e) {
                    throw $e;
                }
            }
            return $validatedItems;
        }

        return $value;
    }

    public function __clone()
    {
        parent::__clone();

        if ($this->itemValidator !== null) {
            $this->itemValidator = $this->itemValidator->clone();
        }
    }
}
