<?php

namespace Lemmon;

class ArrayValidator extends FieldValidator
{
    private ?FieldValidator $itemValidator = null;

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
            return ($value === '') ? [] : [$value];
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
}
