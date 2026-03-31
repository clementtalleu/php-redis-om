<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures;

enum StatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}
