<?php

namespace Lemmon;

class SchemaValidator
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
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public function validate(array $input): array
    {
        [$valid, $data, $errors] = $this->tryValidate($input);
        if (!$valid) {
            throw new ValidationException($errors);
        }
        return $data;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{bool, array<string, mixed>, array<string, mixed>}
     */
    public function tryValidate(array $input): array
    {
        $data = [];
        $errors = [];

        foreach ($this->schema as $key => $validator) {
            if ($this->coerceAll) {
                $validator->coerce();
            }

            $value = $input[$key] ?? null;

            try {
                $data[$key] = $validator->validate($value, $key, $input);
            } catch (ValidationException $e) {
                $errors[$key] = $e->getErrors();
            }
        }

        return [!$errors, $data, $errors];
    }
}
