<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Client;

use Talleu\RedisOm\Client\RedisClient;

class Client extends RedisClient
{
    public function __construct()
    {
        parent::__construct();
        $this->redis->pconnect('redis');
    }
}
