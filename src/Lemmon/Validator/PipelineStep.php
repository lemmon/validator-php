<?php

declare(strict_types=1);

namespace Lemmon\Validator;

/**
 * A single operation in a validator's processing pipeline.
 *
 * @internal
 */
final readonly class PipelineStep
{
    /**
     * @param \Closure(mixed, PipelineContext, string, mixed): mixed $operation The operation
     *                               applied to the value during validation.
     * @param bool     $skipNull     Whether the step is skipped when the value is null.
     * @param (\Closure(): \Closure)|null $rebuildOperation Rebuilds $operation from a fresh clone
     *                               of any captured FieldValidator operand, so cloning isolates state.
     */
    public function __construct(
        public \Closure $operation,
        public bool $skipNull,
        public ?\Closure $rebuildOperation = null,
    ) {}

    /**
     * Returns a copy of this step with a different operation.
     */
    public function withOperation(\Closure $operation): self
    {
        return new self(
            $operation,
            $this->skipNull,
            $this->rebuildOperation,
        );
    }
}
