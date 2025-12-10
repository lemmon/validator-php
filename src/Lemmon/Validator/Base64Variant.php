<?php

declare(strict_types=1);

namespace Lemmon\Validator;

/**
 * Enum for Base64 encoding variants.
 */
enum Base64Variant: string
{
    /**
     * Standard Base64 encoding (uses +, /, and = padding).
     * This is the default variant.
     */
    case Standard = 'standard';

    /**
     * URL-safe Base64 encoding (uses -, _, and no padding).
     * URL-safe parsers typically accept both standard and URL-safe variants.
     */
    case UrlSafe = 'urlsafe';

    /**
     * Accept both standard and URL-safe Base64 variants.
     * Useful when the source format is unknown or mixed.
     */
    case Any = 'any';
}
