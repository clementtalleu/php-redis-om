<?php

namespace Talleu\RedisOm\ApiPlatform\Filters;

enum SearchStrategy: string
{
    case Exact = 'exact';
    case Partial = 'partial';
    case Start = 'start';
    case End = 'end';
}
