<?php

declare(strict_types=1);

namespace Lemmon\Validator;

class ValidationException extends \Exception
{
    /**
     * @param array<array-key, mixed> $errors
     */
    public function __construct(
        private array $errors,
    ) {
        $message = json_encode($errors, JSON_PRETTY_PRINT);
        parent::__construct($message ?? 'JSON encoding of errors failed');
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
