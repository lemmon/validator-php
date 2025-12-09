<?php

declare(strict_types=1);

namespace Lemmon\Validator;

class StringValidator extends FieldValidator
{
    use OneOfTrait;

    /**
     * @inheritDoc
     */
    protected function coerceValue(mixed $value): mixed
    {
        return is_scalar($value) ? (string) $value : $value;
    }

    /**
     * @inheritDoc
     */
    protected function getValidatorType(): string
    {
        return 'string';
    }

    /**
     * @inheritDoc
     */
    protected function validateType(mixed $value, string $key): mixed
    {
        if (!is_string($value)) {
            throw new ValidationException(['Value must be a string']);
        }
        return $value;
    }

    public function email(null|string $message = null): static
    {
        return $this->satisfies(
            fn($value, $key = null, $input = null) => (
                filter_var($value, FILTER_VALIDATE_EMAIL) !== false
            ),
            $message ?? 'Value must be a valid email address',
        );
    }

    public function url(null|string $message = null): static
    {
        return $this->satisfies(
            fn($value, $key = null, $input = null) => (
                filter_var($value, FILTER_VALIDATE_URL) !== false
            ),
            $message ?? 'Value must be a valid URL',
        );
    }

    public function uuid(null|string $message = null): static
    {
        return $this->satisfies(
            fn($value, $key = null, $input = null) => (
                preg_match(
                    '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                    $value,
                ) === 1
            ),
            $message ?? 'Value must be a valid UUID',
        );
    }

    public function ip(null|string $message = null): static
    {
        return $this->satisfies(
            fn($value, $key = null, $input = null) => (
                filter_var($value, FILTER_VALIDATE_IP) !== false
            ),
            $message ?? 'Value must be a valid IP address',
        );
    }

    public function minLength(int $min, null|string $message = null): static
    {
        return $this->satisfies(
            fn($value, $key = null, $input = null) => mb_strlen($value) >= $min,
            $message ?? "Value must be at least {$min} characters long",
        );
    }

    public function maxLength(int $max, null|string $message = null): static
    {
        return $this->satisfies(
            fn($value, $key = null, $input = null) => mb_strlen($value) <= $max,
            $message ?? "Value must be at most {$max} characters long",
        );
    }

    public function length(int $exact, null|string $message = null): static
    {
        return $this->satisfies(
            fn($value, $key = null, $input = null) => mb_strlen($value) === $exact,
            $message ?? "Value must be exactly {$exact} characters long",
        );
    }

    public function pattern(string $regex, null|string $message = null): static
    {
        return $this->satisfies(
            fn($value, $key = null, $input = null) => preg_match($regex, $value) === 1,
            $message ?? 'Value does not match the required pattern',
        );
    }

    public function datetime(string $format = 'Y-m-d\TH:i:s', null|string $message = null): static
    {
        return $this->satisfies(
            function ($value, $key = null, $input = null) use ($format) {
                $date = \DateTime::createFromFormat($format, $value);
                return $date !== false && $date->format($format) === $value;
            },
            $message ?? "Value must be a valid datetime in format '{$format}'",
        );
    }

    public function date(string $format = 'Y-m-d', null|string $message = null): static
    {
        return $this->satisfies(
            function ($value, $key = null, $input = null) use ($format) {
                $date = \DateTime::createFromFormat($format, $value);
                return $date !== false && $date->format($format) === $value;
            },
            $message ?? "Value must be a valid date in format '{$format}'",
        );
    }
}
