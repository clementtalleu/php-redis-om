<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Client;

enum RedisCommands: string
{
    case JSON_DELETE = 'JSON.DEL';
    case JSON_GET = 'JSON.GET';
    case JSON_SET = 'JSON.SET';
    case JSON_MSET = 'JSON.MSET';
    case CREATE_INDEX = 'FT.CREATE';
    case DROP_INDEX = 'FT.DROPINDEX';
    case SEARCH = 'FT.SEARCH';
}
