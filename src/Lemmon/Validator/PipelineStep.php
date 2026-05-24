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
     * @param \Closure $operation    The operation applied to the value during validation.
     * @param bool     $skipNull     Whether the step is skipped when the value is null.
     * @param bool     $bindable     Whether $operation is a non-static closure that must be
     *                               re-bound to the owning validator when it is cloned.
     * @param \Closure|null $rebuildOperation Rebuilds $operation from a fresh clone of any
     *                               captured FieldValidator operand, so cloning isolates state.
     */
    public function __construct(
        public PipelineType $type,
        public \Closure $operation,
        public bool $skipNull,
        public bool $bindable,
        public ?\Closure $rebuildOperation = null,
    ) {}

    /**
     * Returns a copy of this step with a different operation.
     */
    public function withOperation(\Closure $operation): self
    {
        return new self(
            $this->type,
            $operation,
            $this->skipNull,
            $this->bindable,
            $this->rebuildOperation,
        );
    }
}
