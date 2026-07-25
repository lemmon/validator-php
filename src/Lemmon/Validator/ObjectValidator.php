<?php

declare(strict_types=1);

namespace Lemmon\Validator;

class ObjectValidator extends FieldValidator
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
        return 'object';
    }

    /**
     * @inheritDoc
     */
    protected function coerceValue(mixed $value): mixed
    {
        if (is_array($value) && ($value === [] || !array_is_list($value))) {
            return (object) $value;
        }

        // Form-safe: empty string means "no value provided"
        if ($value === '') {
            return null;
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
        if (!$value instanceof \stdClass) {
            throw self::typeError('Input must be a stdClass object', 'stdClass');
        }

        $data = new \stdClass();
        /** @var array<ValidationError> $errors */
        $errors = [];

        foreach ($this->schema as $fieldKey => $validator) {
            // A numeric-string schema key ("2") normalizes to an int array key, but stdClass
            // property names are always strings -- property_exists() and tryValidate() require it.
            $fieldKeyName = (string) $fieldKey;

            // Get field value (null if not present)
            $fieldValue = property_exists($value, $fieldKeyName) ? $value->{$fieldKeyName} : null;

            [$valid, $validatedFieldValue, $fieldErrors] = $validator->tryValidate(
                $fieldValue,
                $fieldKeyName,
                $value,
            );

            if (!$valid) {
                foreach ($fieldErrors as $error) {
                    // Raw $fieldKey (not $fieldKeyName): withPathPrefix() preserves the int/string
                    // distinction for numeric keys -- see ArrayValidator's identical (string) split.
                    $errors[] = $error->withPathPrefix($fieldKey);
                }
                continue;
            }

            // Include fields that were provided in input OR have default values applied
            $wasProvided = property_exists($value, $fieldKeyName);
            $hasDefault = $validator->hasDefault;

            if ($wasProvided || $hasDefault) {
                $dataKey = $validator->outputKey ?? $fieldKey;
                $data->{$dataKey} = $validatedFieldValue;
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        if ($this->passthrough) {
            foreach (\get_object_vars($value) as $inputKey => $rawValue) {
                if (\array_key_exists($inputKey, $this->schema) || \property_exists($data, $inputKey)) {
                    continue;
                }
                $data->{$inputKey} = $rawValue;
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
