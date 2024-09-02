<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Client\Helper;

final class Converter
{
    public static function prefix(string $key): string
    {
        return str_replace('\\', '_', $key);
    }
}
