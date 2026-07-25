<?php

declare(strict_types=1);

namespace Lemmon\Validator;

/**
 * A single structured validation error.
 *
 * Carries a stable machine-readable {@see $code} (decoupled from the human {@see $message},
 * so wording can change without breaking integrators), a dotted {@see $path} locating the
 * failure within nested input (`''` at the root), exact {@see $segments} that preserve literal
 * dots and distinguish integer indices from string keys, and the {@see $params} that produced
 * the message (e.g. `['min' => 5]`), available for i18n re-rendering.
 */
final readonly class ValidationError implements \JsonSerializable
{
    /**
     * Placeholder substituted for a value that cannot be rendered or JSON-encoded.
     */
    private const COMPLEX_VALUE = '(complex value)';

    private string $path;

    /** @var list<int|string> */
    private array $segments;

    private string $code;

    private string $message;

    /** @var array<string, mixed> */
    private array $params;

    /**
     * The string fields are normalised to valid UTF-8 here, at the one point every error is built,
     * so {@see getMessage()} and the JSON form can never carry bytes that make json_encode() fail --
     * no rule has to sanitise what it emits. Params keep their original values and types for i18n
     * re-rendering and are made JSON-safe only at serialization ({@see jsonSerialize()}).
     *
     * A string path is split on dots for backward compatibility. Pass a segment list when a key
     * contains a literal dot, is empty, or must retain the distinction between an integer index
     * and a numeric string key.
     *
     * @param string|list<int|string> $path
     * @param array<string, mixed> $params
     */
    public function __construct(string|array $path, string $code, string $message, array $params = [])
    {
        $segments = is_string($path) ? self::segmentsFromString($path) : $path;
        $this->segments = array_map(
            static fn(int|string $segment): int|string => is_string($segment) ? self::utf8Safe($segment) : $segment,
            $segments,
        );
        $this->path = implode('.', $this->segments);
        $this->code = self::utf8Safe($code);
        $this->message = self::utf8Safe($message);
        $this->params = $params;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Returns the exact path segments. Use this when input keys may contain dots, be empty, or
     * overlap with integer array indices; {@see getPath()} remains the convenient dotted view.
     *
     * @return list<int|string>
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Returns a copy with $prefix prepended to the path, used as errors bubble up through
     * nested schemas (e.g. a child error at `street` becomes `address.street`).
     */
    public function withPathPrefix(int|string $prefix): self
    {
        return new self([$prefix, ...$this->segments], $this->code, $this->message, $this->params);
    }

    /**
     * Substitutes `{name}` tokens in a message template with the matching params.
     *
     * @param array<string, mixed> $params
     */
    public static function interpolate(string $template, array $params): string
    {
        if ($params === []) {
            return $template;
        }

        $replacements = [];
        foreach ($params as $name => $value) {
            $replacements['{' . $name . '}'] = self::stringify($value);
        }

        return strtr($template, $replacements);
    }

    /**
     * Serializes to the public error shape. The string fields are already valid UTF-8 (normalised
     * in the constructor); each param value is passed through {@see jsonSafe()} and each param key
     * through {@see utf8Safe()} (a custom satisfies() call can supply a key with malformed UTF-8,
     * which the surrounding object cast would otherwise carry through and make json_encode() fail).
     * So json_encode() on a ValidationError -- the advertised API payload -- can never throw or
     * return false, whatever key or value a rule put in the error.
     *
     * Params are an object (cast from the string-keyed array) so the JSON shape stays
     * predictable -- an empty params map is `{}`, never `[]`.
     *
     * @return array{path: string, code: string, message: string, params: \stdClass}
     */
    public function jsonSerialize(): array
    {
        $params = [];
        foreach ($this->params as $name => $value) {
            $params[self::utf8Safe((string) $name)] = self::jsonSafe($value);
        }

        return [
            'path' => $this->path,
            'code' => $this->code,
            'message' => $this->message,
            'params' => (object) $params,
        ];
    }

    /**
     * Safely renders a value for inclusion in a human-readable message: strings are returned as
     * valid UTF-8 (invalid bytes substituted, so a rendered value can never make json_encode() on
     * the resulting message fail), other scalars are cast directly, null becomes an empty string,
     * and anything else is JSON-encoded.
     *
     * Rendering must never throw -- a non-stringable object or a param with a throwing
     * jsonSerialize() yields the (complex value) placeholder rather than escalating into breaking
     * validation control flow.
     */
    public static function stringify(mixed $value): string
    {
        if (is_string($value)) {
            return self::utf8Safe($value);
        }

        // Casting a non-finite float to string raises an E_WARNING; render it explicitly instead.
        if (is_float($value) && !is_finite($value)) {
            return match (true) {
                is_nan($value) => 'NAN',
                $value > 0 => 'INF',
                default => '-INF',
            };
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if ($value === null) {
            return '';
        }

        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return self::COMPLEX_VALUE;
        }
    }

    /**
     * Normalises a param value to a form that always re-encodes cleanly: it is JSON round-tripped so
     * the result holds only plain scalars/arrays/stdClass, and an un-encodable value (a throwing
     * jsonSerialize(), a resource, NAN/INF, or a string with invalid UTF-8) collapses to
     * {@see COMPLEX_VALUE}. The original typed value stays available via {@see getParams()}; only the
     * JSON payload uses this.
     *
     * Returning the round-tripped result rather than $value is deliberate: it strips any
     * JsonSerializable wrapper so the outer encoder never invokes a param's jsonSerialize() a second
     * time. A non-deterministic one that encodes cleanly here but throws on the next call would
     * otherwise leak that throwable out of json_encode() -- and out of tryValidate(), which only
     * catches ValidationException.
     */
    private static function jsonSafe(mixed $value): mixed
    {
        try {
            $encoded = json_encode($value, JSON_THROW_ON_ERROR);

            return json_decode($encoded, false, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return self::COMPLEX_VALUE;
        }
    }

    /**
     * Forces a string to valid UTF-8 so it can never make json_encode() throw or return false.
     * Already-valid strings (the common case) are returned unchanged; malformed byte sequences are
     * replaced with U+FFFD, the same substitution JSON_INVALID_UTF8_SUBSTITUTE performs, returned
     * as a plain PHP string.
     */
    private static function utf8Safe(string $value): string
    {
        // The /u modifier makes preg_match fail on malformed UTF-8; a clean string short-circuits.
        if ($value === '' || preg_match('//u', $value) === 1) {
            return $value;
        }

        // Encoding a string with substitution always succeeds and round-trips back to a string, so
        // the false and non-string branches are unreachable at runtime; they remain only to satisfy
        // json_encode()'s string|false and json_decode()'s mixed return types for the analyzer.
        $encoded = json_encode($value, JSON_INVALID_UTF8_SUBSTITUTE);
        $decoded = $encoded === false ? null : json_decode($encoded);

        return is_string($decoded) ? $decoded : self::COMPLEX_VALUE;
    }

    /**
     * @return list<string>
     */
    private static function segmentsFromString(string $path): array
    {
        return $path === '' ? [] : explode('.', $path);
    }
}
