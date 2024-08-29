<?php

namespace Talleu\RedisOm\Client\Helper;

class Converter
{
    public static function prefix(string $key): string
    {
        return str_replace('\\', '_', $key);
    }
}
