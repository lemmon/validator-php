<?php

declare(strict_types=1);

namespace Lemmon\Validator;

/**
 * Enum for UUID version variants.
 */
enum UuidVariant: string
{
    /**
     * Accept any UUID version (1-7).
     */
    case Any = 'any';

    /**
     * Accept only UUID version 1 (time-based).
     */
    case V1 = 'v1';

    /**
     * Accept only UUID version 2 (DCE Security).
     */
    case V2 = 'v2';

    /**
     * Accept only UUID version 3 (name-based with MD5).
     */
    case V3 = 'v3';

    /**
     * Accept only UUID version 4 (random).
     */
    case V4 = 'v4';

    /**
     * Accept only UUID version 5 (name-based with SHA-1).
     */
    case V5 = 'v5';

    /**
     * Accept only UUID version 7 (Unix timestamp-based, sortable).
     */
    case V7 = 'v7';
}
