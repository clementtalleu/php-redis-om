<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Client;

class RedisCommands
{
    public const JSON_DELETE = 'JSON.DEL';
    public const JSON_GET = 'JSON.GET';
    public const JSON_SET = 'JSON.SET';
    public const CREATE_INDEX = 'FT.CREATE';
    public const DROP_INDEX = 'FT.DROPINDEX';
    public const SEARCH = 'FT.SEARCH';
}
