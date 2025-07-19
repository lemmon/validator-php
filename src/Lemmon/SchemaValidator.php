<?php

namespace Lemmon;

class SchemaValidator extends FieldValidator
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
        // If it's already an array, return as-is
        if (is_array($value)) {
            return $value;
        }
        // For other types, return as-is; validateType will handle the error
        return $value;
    }

    /**
     * @inheritDoc
     */
    protected function validateType(mixed $value, string $key): mixed
    {
        if (!is_array($value)) {
            throw new ValidationException(['Value must be an array.']);
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