<?php

namespace Lemmon\Validator;

class ObjectValidator extends FieldValidator
{
    private bool $coerceAll = false;

    /**
     * @param array<string, FieldValidator> $schema
     */
    public function __construct(
        private array $schema
    ) {
    }

    /**
     * @inheritDoc
     */
    protected function getValidatorType(): string
    {
        return 'object';
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
        if (is_array($value)) {
            return (object) $value;
        }

        // Form-safe: empty string becomes empty object
        if ($value === '') {
            return new \stdClass();
        }

        // For other types (including objects), return as-is.
        // The subsequent validateType method will handle non-object errors.
        return $value;
    }

    /**
     * @inheritDoc
     */
    protected function validateType(mixed $value, string $key): mixed
    {
        if (!is_object($value)) {
            throw new ValidationException(['Input must be an object']);
        }

        $data = new \stdClass();
        $errors = [];

        foreach ($this->schema as $fieldKey => $validator) {
            if ($this->coerceAll) {
                $validator->coerce();
            }

            // Get field value (null if not present)
            $fieldValue = property_exists($value, $fieldKey) ? $value->{$fieldKey} : null;

            [$valid, $validatedFieldValue, $fieldErrors] = $validator->tryValidate($fieldValue, $fieldKey, $value);

            if (!$valid) {
                $errors[$fieldKey] = $fieldErrors;
            } else {
                // Include fields that were provided in input OR have default values applied
                $wasProvided = property_exists($value, $fieldKey);
                $hasDefault = $validator->hasDefault ?? false;

                if ($wasProvided || $hasDefault) {
                    $data->{$fieldKey} = $validatedFieldValue;
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $data;
    }
}
