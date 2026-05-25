<?php

declare(strict_types=1);

namespace Lemmon\Validator;

/**
 * Catalog of stable error codes emitted by built-in validators.
 *
 * These are part of the public contract: integrators match on them for programmatic handling
 * and i18n. Custom rules via {@see FieldValidator::satisfies()} default to {@see self::CUSTOM}
 * but may supply any string code.
 */
final class ValidationCode
{
    // Core
    public const REQUIRED = 'REQUIRED';
    public const INVALID_TYPE = 'INVALID_TYPE';
    public const CUSTOM = 'CUSTOM';
    public const IN = 'IN';
    public const CONST = 'CONST';
    public const ENUM = 'ENUM';

    // Logical combinators
    public const ALL_OF = 'ALL_OF';
    public const ANY_OF = 'ANY_OF';
    public const NONE_OF = 'NONE_OF';

    // String
    public const EMAIL = 'EMAIL';
    public const URL = 'URL';
    public const UUID = 'UUID';
    public const IP = 'IP';
    public const STRING_TOO_SHORT = 'STRING_TOO_SHORT';
    public const STRING_TOO_LONG = 'STRING_TOO_LONG';
    public const STRING_LENGTH = 'STRING_LENGTH';
    public const STRING_BETWEEN = 'STRING_BETWEEN';
    public const NOT_EMPTY = 'NOT_EMPTY';
    public const PATTERN = 'PATTERN';
    public const DATETIME = 'DATETIME';
    public const DATE = 'DATE';
    public const HOSTNAME = 'HOSTNAME';
    public const DOMAIN = 'DOMAIN';
    public const TIME = 'TIME';
    public const BASE64 = 'BASE64';
    public const HEX = 'HEX';

    // Numeric
    public const NUMBER_TOO_SMALL = 'NUMBER_TOO_SMALL';
    public const NUMBER_TOO_LARGE = 'NUMBER_TOO_LARGE';
    public const NUMBER_BETWEEN = 'NUMBER_BETWEEN';
    public const GREATER_THAN = 'GREATER_THAN';
    public const LESS_THAN = 'LESS_THAN';
    public const MULTIPLE_OF = 'MULTIPLE_OF';
    public const POSITIVE = 'POSITIVE';
    public const NEGATIVE = 'NEGATIVE';
    public const PORT = 'PORT';

    // Array
    public const ARRAY_TOO_FEW_ITEMS = 'ARRAY_TOO_FEW_ITEMS';
    public const ARRAY_TOO_MANY_ITEMS = 'ARRAY_TOO_MANY_ITEMS';
    public const CONTAINS = 'CONTAINS';
    public const NOT_UNIQUE = 'NOT_UNIQUE';
}
