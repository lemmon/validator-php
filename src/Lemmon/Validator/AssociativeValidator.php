<?php

declare(strict_types=1);

namespace Lemmon\Validator;

class AssociativeValidator extends FieldValidator
{
    use SchemaValidatorOptionsTrait;

    /**
     * @param array<string, FieldValidator> $schema
     */
    public function __construct(
        private array $schema,
    ) {
        foreach ($this->schema as $key => $validator) {
            $this->schema[$key] = $validator->clone();
        }
    }

    /**
     * @inheritDoc
     */
    protected function getValidatorType(): string
    {
        return 'associative_array';
    }

    /**
     * @inheritDoc
     */
    protected function coerceValue(mixed $value): mixed
    {
        if ($value instanceof \stdClass) {
            return (array) $value;
        }

        // Form-safe: empty string means "no value provided"
        if ($value === '') {
            return null;
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
        if (!is_array($value) || $value !== [] && array_is_list($value)) {
            throw self::typeError('Input must be an associative array', 'associative_array');
        }

        $data = [];
        /** @var array<ValidationError> $errors */
        $errors = [];

        foreach ($this->schema as $fieldKey => $validator) {
            // Get field value (null if not present)
            $fieldValue = array_key_exists($fieldKey, $value) ? $value[$fieldKey] : null;

            [$valid, $validatedFieldValue, $fieldErrors] = $validator->tryValidate(
                $fieldValue,
                (string) $fieldKey,
                $value,
            );

            if (!$valid) {
                foreach ($fieldErrors as $error) {
                    // Not (string) $fieldKey: a numeric-string schema key ("2") normalizes to an
                    // int array key, and withPathPrefix() preserves that distinction from a
                    // literal string segment -- see ArrayValidator's identical (string) split.
                    $errors[] = $error->withPathPrefix($fieldKey);
                }
                continue;
            }

            // Include fields that were provided in input OR have default values applied
            $wasProvided = array_key_exists($fieldKey, $value);
            $hasDefault = $validator->hasDefault;

            if ($wasProvided || $hasDefault) {
                $dataKey = $validator->outputKey ?? $fieldKey;
                $data[$dataKey] = $validatedFieldValue;
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        if ($this->passthrough) {
            foreach ($value as $inputKey => $rawValue) {
                if (\array_key_exists($inputKey, $this->schema) || \array_key_exists($inputKey, $data)) {
                    continue;
                }
                $data[$inputKey] = $rawValue;
            }
        }

        return $data;
    }

    public function __clone()
    {
        parent::__clone();
        $this->cloneSchemaFieldValidators();
    }
}
