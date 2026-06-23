<?php

declare(strict_types=1);

namespace Lemmon\Tests\Fixtures;

/**
 * A named (serializable) value object whose jsonSerialize() throws. Used to exercise that error
 * rendering never escalates into breaking validation control flow.
 */
final class ThrowingJsonSerializable implements \JsonSerializable
{
    public function jsonSerialize(): mixed
    {
        throw new \RuntimeException('jsonSerialize boom');
    }
}
