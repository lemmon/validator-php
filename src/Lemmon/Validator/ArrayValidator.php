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
                fn($item) => $item !== '' && $item !== null,
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
