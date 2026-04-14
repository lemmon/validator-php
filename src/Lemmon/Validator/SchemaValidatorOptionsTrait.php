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

    /**
     * Recursively enable coercion on every schema field. Nested schema and array validators
     * propagate coercion to their own children.
     *
     * Safe to call on shared definitions: the constructor already clones every field
     * validator, so mutations here stay local to this schema instance.
     *
     * Scope: schema fields, nested schemas, and array item validators. Does not propagate
     * into assertion operands ({@see FieldValidator::satisfies()}, {@see FieldValidator::satisfiesAll()},
     * etc.); call {@see FieldValidator::coerce()} on those validators individually when needed.
     */
    public function coerceAll(): static
    {
        if ($this->coerceAll) {
            return $this;
        }
        $this->coerce = true;
        foreach ($this->schema as $fieldKey => $validator) {
            $this->schema[$fieldKey] = $validator->coerceAll();
        }
        $this->coerceAll = true;

        return $this;
    }

    /**
     * Preserve input members that are not declared in the schema, without validating them.
     *
     * Schema fields are still validated. Output members already set from the schema (including
     * via {@see FieldValidator::outputKey()}) are not overwritten by passthrough values.
     */
    public function passthrough(): static
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
