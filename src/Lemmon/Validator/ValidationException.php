<?php

declare(strict_types=1);

namespace Lemmon\Validator;

class ValidationException extends \Exception
{
    /**
     * @var array<int, ValidationError>
     */
    private array $errors;

    /**
     * @param array<ValidationError> $errors
     */
    public function __construct(array $errors)
    {
        $this->errors = array_values($errors);

        $message = json_encode($this->toLegacyArray(), JSON_PRETTY_PRINT);
        parent::__construct($message === false ? 'Validation failed' : $message);
    }

    /**
     * Structured errors: the source of truth. Each carries a path, code, message, and params.
     *
     * @return array<int, ValidationError>
     */
    public function getStructuredErrors(): array
    {
        return $this->errors;
    }

    /**
     * Legacy nested error view, derived from the structured errors. Root-level errors are a flat
     * list of messages; nested errors are keyed by path segment with a message list at the leaf.
     *
     * @return array<array-key, mixed>
     */
    public function getErrors(): array
    {
        return $this->toLegacyArray();
    }

    /**
     * Flattened list suitable for API consumption. Each entry has:
     * - 'path': dotted field path ('_root' for root-level errors)
     * - 'message': error message
     *
     * @return array<int, array{path: string, message: string}>
     */
    public function getFlattenedErrors(): array
    {
        return self::flattenErrors($this->errors);
    }

    /**
     * Flattens a list of structured errors into path/message pairs.
     *
     * Useful for flattening errors from `tryValidate()` results:
     * ```php
     * [$valid, $data, $errors] = $validator->tryValidate($value);
     * if (!$valid) {
     *     $flattened = ValidationException::flattenErrors($errors);
     * }
     * ```
     *
     * @param array<ValidationError>|null $errors The structured errors (null returns empty array)
     * @return array<int, array{path: string, message: string}>
     */
    public static function flattenErrors(?array $errors): array
    {
        if ($errors === null) {
            return [];
        }

        return array_map(
            static fn(ValidationError $error): array => [
                'path' => $error->getPath() === '' ? '_root' : $error->getPath(),
                'message' => $error->getMessage(),
            ],
            array_values($errors),
        );
    }

    /**
     * Rebuilds the legacy nested structure from the flat structured errors.
     *
     * @return array<array-key, mixed>
     */
    private function toLegacyArray(): array
    {
        $result = [];

        foreach ($this->errors as $error) {
            $segments = $error->getPath() === '' ? [] : explode('.', $error->getPath());
            $result = self::insertMessage($result, $segments, $error->getMessage());
        }

        return $result;
    }

    /**
     * Inserts a message into the nested structure at the location given by the path segments.
     * An empty segment list appends the message to a root-level message list.
     *
     * @param array<array-key, mixed> $target
     * @param list<string> $segments
     * @return array<array-key, mixed>
     */
    private static function insertMessage(array $target, array $segments, string $message): array
    {
        if ($segments === []) {
            $target[] = $message;

            return $target;
        }

        $segment = array_shift($segments);
        $existing = $target[$segment] ?? null;
        $child = is_array($existing) ? $existing : [];
        $target[$segment] = self::insertMessage($child, $segments, $message);

        return $target;
    }
}
