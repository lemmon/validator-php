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
        if ($message === false) {
            $message = 'JSON encoding of errors failed';
        }
        parent::__construct($message);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Returns a flattened list of errors suitable for API consumption.
     *
     * Each error entry contains:
     * - 'path': Field path ('_root' for root-level scalar/container errors, '_global' reserved for future cross-field validation)
     * - 'message': Error message
     *
     * @return array<int, array{path: string, message: string}>
     */
    public function getFlattenedErrors(): array
    {
        return self::flattenErrors($this->errors);
    }

    /**
     * Flattens an error array structure into a list suitable for API consumption.
     *
     * Useful for flattening errors from `tryValidate()` results:
     * ```php
     * [$valid, $data, $errors] = $validator->tryValidate($value);
     * if (!$valid) {
     *     $flattened = ValidationException::flattenErrors($errors);
     * }
     * ```
     *
     * Each error entry contains:
     * - 'path': Field path ('_root' for root-level scalar/container errors, '_global' reserved for future cross-field validation)
     * - 'message': Error message
     *
     * @param array<array-key, mixed>|null $errors The error structure to flatten (null returns empty array)
     * @return array<int, array{path: string, message: string}>
     */
    public static function flattenErrors(null|array $errors): array
    {
        if ($errors === null) {
            return [];
        }

        return self::flattenErrorsRecursive($errors, '');
    }

    /**
     * Recursively flattens nested error structure into a list of path/message pairs.
     *
     * @param array<array-key, mixed> $errors The error structure to flatten
     * @param string $basePath The base path prefix for building full paths
     * @return array<int, array{path: string, message: string}>
     */
    private static function flattenErrorsRecursive(array $errors, string $basePath): array
    {
        $result = [];

        // Check if this is a flat array of strings (scalar/root-level errors)
        if (self::isFlatErrorArray($errors)) {
            // This is a root-level error (scalar validator or container type error)
            $path = $basePath === '' ? '_root' : $basePath;
            foreach ($errors as $message) {
                if (!is_string($message)) {
                    continue;
                }
                $result[] = ['path' => $path, 'message' => $message];
            }
            return $result;
        }

        // This is a nested structure - recurse through it
        foreach ($errors as $key => $value) {
            $currentPath = self::buildPath($basePath, (string) $key);

            if (!is_array($value)) {
                if (is_string($value)) {
                    // Single string error at this level
                    $result[] = ['path' => $currentPath, 'message' => $value];
                }
                continue;
            }

            if (self::isFlatErrorArray($value)) {
                // This is a field-level error array
                foreach ($value as $message) {
                    if (!is_string($message)) {
                        continue;
                    }
                    $result[] = ['path' => $currentPath, 'message' => $message];
                }
                continue;
            }

            // This is a nested structure - recurse
            $result = array_merge($result, self::flattenErrorsRecursive($value, $currentPath));
        }

        return $result;
    }

    /**
     * Checks if an array is a flat array of strings (error messages).
     *
     * @param array<array-key, mixed> $array
     * @return bool
     */
    private static function isFlatErrorArray(array $array): bool
    {
        // Empty array is considered flat
        if ($array === []) {
            return false;
        }

        // Check if all values are strings and keys are numeric (0-indexed)
        foreach ($array as $key => $value) {
            if (is_int($key) && is_string($value)) {
                continue;
            }
            return false;
        }

        // Check if keys are sequential starting from 0
        $keys = array_keys($array);
        return $keys === range(0, count($array) - 1);
    }

    /**
     * Builds a path string by concatenating base path with a key.
     *
     * @param string $basePath The base path
     * @param string $key The key to append
     * @return string The combined path
     */
    private static function buildPath(string $basePath, string $key): string
    {
        if ($basePath === '') {
            return $key;
        }

        return $basePath . '.' . $key;
    }
}
