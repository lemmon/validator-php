<?php

declare(strict_types=1);

namespace Lemmon\Validator;

/**
 * Shared options and helpers for {@see AssociativeValidator} and {@see ObjectValidator}.
 *
 * Expects the using class to define a private `array<string, FieldValidator> $schema`.
 */
trait SchemaValidatorOptionsTrait
{
    private bool $coerceAll = false;

    private bool $passthrough = false;

    public function coerceAll(): self
    {
        $this->coerceAll = true;
        return $this;
    }

    /**
     * Preserve input members that are not declared in the schema, without validating them.
     *
     * Schema fields are still validated. Output members already set from the schema (including
     * via {@see FieldValidator::outputKey()}) are not overwritten by passthrough values.
     */
    public function passthrough(): self
    {
        $this->passthrough = true;
        return $this;
    }

    /**
     * Deep-clone validators in the schema; call from {@see __clone()} after `parent::__clone()`.
     */
    protected function cloneSchemaFieldValidators(): void
    {
        foreach ($this->schema as $fieldKey => $validator) {
            $this->schema[$fieldKey] = $validator->clone();
        }
    }
}
