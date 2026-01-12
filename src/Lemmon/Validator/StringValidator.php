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
            static fn ($value, $key = null, $input = null) => (
                filter_var($value, FILTER_VALIDATE_EMAIL) !== false
            ),
            $message ?? 'Value must be a valid email address',
        );
    }

    public function url(null|string $message = null): static
    {
        return $this->satisfies(
            static fn ($value, $key = null, $input = null) => (
                filter_var($value, FILTER_VALIDATE_URL) !== false
            ),
            $message ?? 'Value must be a valid URL',
        );
    }

    public function uuid(
        UuidVariant $variant = UuidVariant::Any,
        null|string $message = null,
    ): static {
        return $this->satisfies(
            static fn ($value, $key = null, $input = null) => match ($variant) {
                UuidVariant::Any => preg_match(
                    '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-7][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                    $value,
                ) === 1,
                UuidVariant::V1 => preg_match(
                    '/^[0-9a-f]{8}-[0-9a-f]{4}-1[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                    $value,
                ) === 1,
                UuidVariant::V2 => preg_match(
                    '/^[0-9a-f]{8}-[0-9a-f]{4}-2[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                    $value,
                ) === 1,
                UuidVariant::V3 => preg_match(
                    '/^[0-9a-f]{8}-[0-9a-f]{4}-3[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                    $value,
                ) === 1,
                UuidVariant::V4 => preg_match(
                    '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                    $value,
                ) === 1,
                UuidVariant::V5 => preg_match(
                    '/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                    $value,
                ) === 1,
                UuidVariant::V7 => preg_match(
                    '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                    $value,
                ) === 1,
            },
            $message ?? match ($variant) {
                UuidVariant::V1 => 'Value must be a valid UUID version 1',
                UuidVariant::V2 => 'Value must be a valid UUID version 2',
                UuidVariant::V3 => 'Value must be a valid UUID version 3',
                UuidVariant::V4 => 'Value must be a valid UUID version 4',
                UuidVariant::V5 => 'Value must be a valid UUID version 5',
                UuidVariant::V7 => 'Value must be a valid UUID version 7',
                UuidVariant::Any => 'Value must be a valid UUID',
            },
        );
    }

    public function ip(IpVersion $version = IpVersion::Any, null|string $message = null): static
    {
        return $this->satisfies(
            static fn ($value, $key = null, $input = null) => match ($version) {
                IpVersion::Any => filter_var($value, FILTER_VALIDATE_IP) !== false,
                IpVersion::IPv4 => filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                    !== false,
                IpVersion::IPv6 => filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
                    !== false,
            },
            $message ?? match ($version) {
                IpVersion::IPv4 => 'Value must be a valid IPv4 address',
                IpVersion::IPv6 => 'Value must be a valid IPv6 address',
                IpVersion::Any => 'Value must be a valid IP address',
            },
        );
    }

    public function minLength(int $min, null|string $message = null): static
    {
        return $this->satisfies(
            static fn ($value, $key = null, $input = null) => mb_strlen($value) >= $min,
            $message ?? "Value must be at least {$min} characters long",
        );
    }

    public function maxLength(int $max, null|string $message = null): static
    {
        return $this->satisfies(
            static fn ($value, $key = null, $input = null) => mb_strlen($value) <= $max,
            $message ?? "Value must be at most {$max} characters long",
        );
    }

    public function length(int $exact, null|string $message = null): static
    {
        return $this->satisfies(
            static fn ($value, $key = null, $input = null) => mb_strlen($value) === $exact,
            $message ?? "Value must be exactly {$exact} characters long",
        );
    }

    public function notEmpty(null|string $message = null): static
    {
        return $this->minLength(1, $message ?? 'Value must not be empty');
    }

    public function pattern(string $regex, null|string $message = null): static
    {
        return $this->satisfies(
            static fn ($value, $key = null, $input = null) => preg_match($regex, $value) === 1,
            $message ?? 'Value does not match the required pattern',
        );
    }

    public function datetime(string $format = 'Y-m-d\TH:i:s', null|string $message = null): static
    {
        return $this->satisfies(
            static function ($value, $key = null, $input = null) use ($format) {
                $date = \DateTime::createFromFormat($format, $value);
                return $date !== false && $date->format($format) === $value;
            },
            $message ?? "Value must be a valid datetime in format '{$format}'",
        );
    }

    public function date(string $format = 'Y-m-d', null|string $message = null): static
    {
        return $this->satisfies(
            static function ($value, $key = null, $input = null) use ($format) {
                $date = \DateTime::createFromFormat($format, $value);
                return $date !== false && $date->format($format) === $value;
            },
            $message ?? "Value must be a valid date in format '{$format}'",
        );
    }

    public function hostname(null|string $message = null): static
    {
        return $this->satisfies(
            static fn ($value, $key = null, $input = null) => (
                filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
            ),
            $message ?? 'Value must be a valid hostname',
        );
    }

    public function domain(null|string $message = null): static
    {
        return $this->satisfies(
            static function ($value, $key = null, $input = null) {
                // Domain must have at least one dot (rejects single labels like 'localhost')
                if (!str_contains($value, '.')) {
                    return false;
                }
                return filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
            },
            $message ?? 'Value must be a valid domain name',
        );
    }

    public function time(null|string $message = null): static
    {
        return $this->satisfies(
            static fn ($value, $key = null, $input = null) => (
                preg_match('/^([01]\d|2[0-3]):([0-5]\d)(:([0-5]\d))?$/', $value) === 1
            ),
            $message ?? 'Value must be a valid time in format HH:MM or HH:MM:SS',
        );
    }

    public function base64(
        Base64Variant $variant = Base64Variant::Standard,
        null|string $message = null,
    ): static {
        return $this->satisfies(
            static fn ($value, $key = null, $input = null) => match ($variant) {
                Base64Variant::Standard => preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $value) === 1
                    && ($decoded = base64_decode($value, true)) !== false
                    && base64_encode($decoded) === $value,
                Base64Variant::UrlSafe => preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $value) === 1 // Try standard Base64 first // URL-safe parsers accept both standard and URL-safe variants
                && ($decoded = base64_decode($value, true)) !== false
                && base64_encode($decoded) === $value
                    // Or try URL-safe Base64
                    || preg_match('/^[A-Za-z0-9\-_]*={0,2}$/', $value) === 1
                    && base64_decode(strtr($value, '-_', '+/'), true) !== false,
                Base64Variant::Any => preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $value) === 1 // Try standard Base64 first
                && ($decoded = base64_decode($value, true)) !== false
                && base64_encode($decoded) === $value
                    // Or try URL-safe Base64
                    || preg_match('/^[A-Za-z0-9\-_]*={0,2}$/', $value) === 1
                    && base64_decode(strtr($value, '-_', '+/'), true) !== false,
            },
            $message ?? match ($variant) {
                Base64Variant::Standard => 'Value must be a valid Base64 encoded string',
                Base64Variant::UrlSafe => 'Value must be a valid URL-safe Base64 encoded string',
                Base64Variant::Any => 'Value must be a valid Base64 encoded string',
            },
        );
    }

    public function hex(null|string $message = null): static
    {
        return $this->satisfies(
            static fn ($value, $key = null, $input = null) => (
                preg_match('/^[0-9a-fA-F]+$/', $value) === 1
                && strlen($value) > 0
            ),
            $message ?? 'Value must be a valid hexadecimal string',
        );
    }

    public function regex(string $pattern, null|string $message = null): static
    {
        return $this->pattern($pattern, $message);
    }
}
