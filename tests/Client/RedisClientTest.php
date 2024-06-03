<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Client;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Client\RedisClient;
use Talleu\RedisOm\Client\RedisClientInterface;

final class RedisClientTest extends TestCase
{
    public function testCreateClient(): void
    {
        $redisClient = (new RedisClient());
        $this->assertInstanceOf(RedisClientInterface::class, $redisClient);
    }
}
