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

    private bool $strict = false;

    private ?string $strictMessage = null;

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
     *
     * Mutually exclusive with {@see strict()}; the last call wins.
     */
    public function passthrough(): static
    {
        $this->passthrough = true;
        $this->strict = false;
        return $this;
    }

    /**
     * Reject input members that are not declared in the schema.
     *
     * Each undeclared key fails with {@see ValidationCode::UNRECOGNIZED_KEY} at that key's path
     * (params: `key`), aggregated with the schema field errors. "Declared" means an input key of
     * the schema, so a field remapped with {@see FieldValidator::outputKey()} is known by its
     * input name only. Applies to this level only; nested schemas opt in on their own.
     *
     * Mutually exclusive with {@see passthrough()}; the last call wins.
     *
     * @param string|null $message Custom error message; may contain the `{key}` placeholder
     */
    public function strict(?string $message = null): static
    {
        $this->strict = true;
        $this->strictMessage = $message;
        $this->passthrough = false;
        return $this;
    }

    /**
     * Builds an {@see ValidationCode::UNRECOGNIZED_KEY} error for every input key not declared
     * in the schema; returns an empty list unless {@see strict()} is enabled.
     *
     * Takes the input container rather than its keys so the default (non-strict) path never
     * enumerates input members -- undeclared keys cost nothing unless strict mode asks for them.
     *
     * @param array<int|string, mixed>|\stdClass $input
     * @return list<ValidationError>
     */
    protected function unrecognizedKeyErrors(array|\stdClass $input): array
    {
        if (!$this->strict) {
            return [];
        }

        // get_object_vars() (not foreach over the object) so numeric property names come back as
        // int keys, matching how numeric schema keys are normalized and reported.
        $members = $input instanceof \stdClass ? \get_object_vars($input) : $input;

        $errors = [];
        foreach ($members as $inputKey => $_) {
            if (\array_key_exists($inputKey, $this->schema)) {
                continue;
            }
            $params = ['key' => $inputKey];
            $errors[] = new ValidationError(
                [$inputKey],
                ValidationCode::UNRECOGNIZED_KEY,
                ValidationError::interpolate($this->strictMessage ?? 'Unrecognized key', $params),
                $params,
            );
        }

        return $errors;
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
