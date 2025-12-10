<?php

declare(strict_types=1);

namespace Lemmon\Validator;

/**
 * Enum for IP address version variants.
 */
enum IpVersion: string
{
    /**
     * Accept both IPv4 and IPv6 addresses.
     */
    case Any = 'any';

    /**
     * Accept only IPv4 addresses.
     */
    case IPv4 = 'ipv4';

    /**
     * Accept only IPv6 addresses.
     */
    case IPv6 = 'ipv6';
}
