<?php

namespace Lemmon;

class AssociativeValidator extends FieldValidator
{
    private bool $coerceAll = false;

    /**
     * @param array<string, FieldValidator> $schema
     */
    public function __construct(
        private array $schema
    ) {
    }

    public function coerceAll(): self
    {
        $this->coerceAll = true;
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function coerceValue(mixed $value): mixed
    {
        if (is_object($value)) {
            return (array) $value;
        }

        // For other types (including arrays), return as-is.
        // The subsequent validateType method will handle non-array errors.
        return $value;
    }

    /**
     * @inheritDoc
     */
    protected function validateType(mixed $value, string $key): mixed
    {
        if (!is_array($value)) {
            throw new ValidationException(['Input must be an associative array.']);
        }

        $data = [];
        $errors = [];

        foreach ($this->schema as $fieldKey => $validator) {
            if ($this->coerceAll) {
                $validator->coerce();
            }

            $fieldValue = $value[$fieldKey] ?? null;

            [$valid, $validatedFieldValue, $fieldErrors] = $validator->tryValidate($fieldValue, $fieldKey, $value);

            (!$valid) ? $errors[$fieldKey] = $fieldErrors : $data[$fieldKey] = $validatedFieldValue;
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $data;
    }
}
