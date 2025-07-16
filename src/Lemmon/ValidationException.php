<?php

namespace Lemmon;

class ValidationException extends \Exception
{
    /**
     * @param array<string> $errors
     */
    public function __construct(
        private array $errors
    ) {
        $message = json_encode($errors, JSON_PRETTY_PRINT);
        parent::__construct($message ?: 'JSON encoding of errors failed.');
    }

    /**
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
