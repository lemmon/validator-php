<?php

namespace Lemmon\Validator;

/**
 * Enum for pipeline operation types.
 *
 * Defines the different types of operations that can be added to the validation pipeline.
 */
enum PipelineType: string
{
    /**
     * Validation operations that check if a value meets certain criteria.
     * These operations skip null values unless the field is required.
     */
    case VALIDATION = 'validation';

    /**
     * Transformation operations that modify or convert values.
     * These operations always execute, even on null values.
     */
    case TRANSFORMATION = 'transformation';
}
