<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Client;

use Talleu\RedisOm\Client\PredisClient;
use Talleu\RedisOm\Client\RedisClient;
use Talleu\RedisOm\Client\RedisClientInterface;

class Client
{
    public ?RedisClientInterface $redisClient = null;

    public function __construct()
    {
        $this->redisClient = getenv('REDIS_CLIENT') === 'predis' ? new PredisClient() : new RedisClient();
        $this->redisClient->createPersistentConnection($_SERVER['REDIS_HOST']);
    }
}
