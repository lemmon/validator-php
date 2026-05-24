<?php

declare(strict_types=1);

namespace Lemmon\Validator;

/**
 * Per-run type context threaded through a validator's pipeline.
 *
 * Created fresh on each {@see FieldValidator::tryValidate()} call and discarded when the run
 * ends, so a validator instance holds no transient state between runs or across clones.
 * {@see FieldValidator::transform()} records the type it produces; {@see FieldValidator::pipe()}
 * reads it to coerce its result back to that type (reindexing list arrays).
 *
 * @internal
 */
final class PipelineContext
{
    private ?string $currentType = null;

    /**
     * @param string $baseType The validator's own type, used until a transform changes it.
     */
    public function __construct(
        private readonly string $baseType,
    ) {}

    /**
     * Records the type produced by a transform step, so later pipe steps coerce against it.
     */
    public function setTypeFrom(mixed $value): void
    {
        $this->currentType = self::detect($value);
    }

    /**
     * Coerces a pipe step's result to stay consistent with the current type: list arrays are
     * reindexed to remain contiguous; associative arrays and scalars are returned unchanged.
     */
    public function coerce(mixed $value): mixed
    {
        return match ($this->currentType ?? $this->baseType) {
            'indexed_array' => is_array($value) && !array_is_list($value) ? array_values($value) : $value,
            default => $value,
        };
    }

    private static function detect(mixed $value): string
    {
        return match (true) {
            is_array($value) && array_is_list($value) => 'indexed_array',
            is_array($value) => 'associative_array',
            is_string($value) => 'string',
            is_int($value) => 'int',
            is_float($value) => 'float',
            is_bool($value) => 'bool',
            is_object($value) => 'object',
            default => 'mixed',
        };
    }
}
