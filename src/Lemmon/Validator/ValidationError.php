<?php

declare(strict_types=1);

namespace Lemmon\Validator;

/**
 * A single structured validation error.
 *
 * Carries a stable machine-readable {@see $code} (decoupled from the human {@see $message},
 * so wording can change without breaking integrators), a dotted {@see $path} locating the
 * failure within nested input (`''` at the root), and the {@see $params} that produced the
 * message (e.g. `['min' => 5]`), available for i18n re-rendering.
 */
final readonly class ValidationError implements \JsonSerializable
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        public string $path,
        public string $code,
        public string $message,
        public array $params = [],
    ) {}

    public function getPath(): string
    {
        return $this->path;
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
    public function withPathPrefix(string $prefix): self
    {
        $path = $this->path === '' ? $prefix : $prefix . '.' . $this->path;

        return new self($path, $this->code, $this->message, $this->params);
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
            $replacements['{' . $name . '}'] = self::stringifyParam($value);
        }

        return strtr($template, $replacements);
    }

    /**
     * Params are serialized as an object (cast from the string-keyed array) so the JSON shape
     * stays predictable -- an empty params map is `{}`, never `[]`.
     *
     * @return array{path: string, code: string, message: string, params: \stdClass}
     */
    public function jsonSerialize(): array
    {
        return [
            'path' => $this->path,
            'code' => $this->code,
            'message' => $this->message,
            'params' => (object) $this->params,
        ];
    }

    private static function stringifyParam(mixed $value): string
    {
        if (is_scalar($value)) {
            return (string) $value;
        }

        if ($value === null) {
            return '';
        }

        $encoded = json_encode($value);

        return $encoded !== false ? $encoded : '';
    }
}
