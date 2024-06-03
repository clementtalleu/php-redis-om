<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Client\RedisClient;
use Talleu\RedisOm\Console\Runner;
use Talleu\RedisOm\Om\RedisObjectManager;
use Talleu\RedisOm\Tests\Fixtures\FixturesGenerator;

class RedisAbstractTestCase extends TestCase
{
    public static function createClient(): RedisClient
    {
        return (new RedisClient());
    }

    public static function emptyRedis(): void
    {
        static::createClient()->flushAll();
    }

    public static function generateIndex(): void
    {
        Runner::generateSchema('tests');
    }

    public static function loadRedisFixtures(string $format): array
    {
        $objectManager = new RedisObjectManager();
        $dummies = FixturesGenerator::generateDummies($format);
        foreach ($dummies as $dummy) {
            $objectManager->persist($dummy);
        }

        $objectManager->flush();
        return $dummies;
    }
}