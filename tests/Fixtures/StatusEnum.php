<?php

declare(strict_types=1);

namespace Lemmon\Tests\Fixtures;

enum StatusEnum: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Done = 'done';
}
