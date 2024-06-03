<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om;

enum RedisFormat: string
{
    case HASH = 'HASH';
    case JSON = 'JSON';
}
