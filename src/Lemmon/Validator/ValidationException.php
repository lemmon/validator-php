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

        // Each ValidationError serializes to a JSON-clean shape (see ValidationError::jsonSerialize),
        // so encoding the list cannot throw; the false branch only honours json_encode()'s
        // string|false signature.
        $message = json_encode($this->errors, JSON_PRETTY_PRINT);
        parent::__construct($message === false ? 'Validation failed' : $message);
    }

    /**
     * Returns the structured validation errors, optionally filtered to a single field.
     *
     * Each error is a {@see ValidationError} carrying a path, code, message, and params, and is
     * {@see \JsonSerializable} as `{path, code, message, params}`.
     *
     * The optional $path filters to a field and everything nested beneath it:
     * - `null` (default) returns every error, unfiltered.
     * - `''` returns only root-level errors (those whose path is the empty string).
     * - `'address'` returns errors at `address` and its subtree (`address.street`, `address.zip`, ...).
     *
     * The trailing-dot match means `getErrors('name')` will not pick up a sibling like `name_full`.
     * Returns an empty list (never null) when nothing matches.
     *
     * @param string|null $path Optional field path to filter by.
     * @return array<int, ValidationError>
     */
    public function getErrors(?string $path = null): array
    {
        if ($path === null) {
            return $this->errors;
        }

        return array_values(array_filter(
            $this->errors,
            // The subtree (trailing-dot) match is skipped at the root: '' means "exactly the root",
            // not "every path" (that is the null default). Without this guard a malformed leading-dot
            // path such as `.hidden` -- which an empty schema key composes into -- would match '.'.
            static fn(ValidationError $error): bool => (
                $error->getPath() === $path
                || $path !== ''
                && str_starts_with($error->getPath(), $path . '.')
            ),
        ));
    }
}
