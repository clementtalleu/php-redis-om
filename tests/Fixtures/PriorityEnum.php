<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures;

enum PriorityEnum: int
{
    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;
}
