<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Client;

use PHPUnit\Framework\TestCase;
use Predis\Client;
use Talleu\RedisOm\Client\RedisClient;

final class RedisClientTest extends TestCase
{
    public function testCreateClient(): void
    {
        $redisClient = RedisClient::createClient();
        $this->assertInstanceOf(Client::class, $redisClient);
    }
}
