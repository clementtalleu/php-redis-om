<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Console;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Command\GenerateSchema;
use Talleu\RedisOm\Console\Runner;

class RunnerTest extends TestCase
{
    public function testRunnerBadDir(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Runner::generateSchema('../bad_dir');
    }

    public function testGenerateSchemaWithInjectedClient(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);

        // The mock client should receive dropIndex and createIndex calls
        // for each entity found in the tests directory
        $redisClient->expects($this->atLeastOnce())->method('dropIndex');
        $redisClient->expects($this->atLeastOnce())->method('createIndex');

        GenerateSchema::generateSchema(__DIR__ . '/../Fixtures', $redisClient);
    }

    public function testRunnerPassesClientToGenerateSchema(): void
    {
        $redisClient = $this->createMock(RedisClientInterface::class);

        $redisClient->expects($this->atLeastOnce())->method('dropIndex');
        $redisClient->expects($this->atLeastOnce())->method('createIndex');

        Runner::generateSchema(__DIR__ . '/../Fixtures', $redisClient);
    }
}
