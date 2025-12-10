<?php

declare(strict_types=1);

namespace Lemmon\Validator;

class AssociativeValidator extends FieldValidator
{
    private bool $coerceAll = false;

    /**
     * @param array<string, FieldValidator> $schema
     */
    public function __construct(
        private array $schema,
    ) {}

    /**
     * @inheritDoc
     */
    protected function getValidatorType(): string
    {
        return 'associative_array';
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

        // Form-safe: empty string becomes empty array
        if ($value === '') {
            return [];
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
            throw new ValidationException(['Input must be an associative array']);
        }

        $data = [];
        $errors = [];

        foreach ($this->schema as $fieldKey => $validator) {
            if ($this->coerceAll) {
                $validator->coerce();
            }

            // Get field value (null if not present)
            $fieldValue = array_key_exists($fieldKey, $value) ? $value[$fieldKey] : null;

            [$valid, $validatedFieldValue, $fieldErrors] = $validator->tryValidate(
                $fieldValue,
                $fieldKey,
                $value,
            );

            if (!$valid) {
                $errors[$fieldKey] = $fieldErrors;
                continue;
            }

            // Include fields that were provided in input OR have default values applied
            $wasProvided = array_key_exists($fieldKey, $value);
            $hasDefault = $validator->hasDefault ?? false;

            if ($wasProvided || $hasDefault) {
                $data[$fieldKey] = $validatedFieldValue;
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $data;
    }

    public function __clone()
    {
        parent::__clone();

        foreach ($this->schema as $fieldKey => $validator) {
            $this->schema[$fieldKey] = $validator->clone();
        }
    }
}
