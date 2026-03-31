<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Mapping;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Om\Converters\HashModel\HashObjectConverter;
use Talleu\RedisOm\Om\Converters\JsonModel\JsonObjectConverter;
use Talleu\RedisOm\Om\Mapping\Entity;
use Talleu\RedisOm\Om\RedisFormat;
use Talleu\RedisOm\Om\Repository\HashModel\HashRepository;
use Talleu\RedisOm\Om\Repository\JsonModel\JsonRepository;

final class EntityAttributeTest extends TestCase
{
    public function testDefaultsToHashFormat(): void
    {
        $entity = new Entity();

        $this->assertNull($entity->format);
        $this->assertInstanceOf(HashObjectConverter::class, $entity->converter);
        $this->assertInstanceOf(HashRepository::class, $entity->repository);
    }

    public function testJsonFormat(): void
    {
        $entity = new Entity(format: RedisFormat::JSON->value);

        $this->assertSame(RedisFormat::JSON->value, $entity->format);
        $this->assertInstanceOf(JsonObjectConverter::class, $entity->converter);
        $this->assertInstanceOf(JsonRepository::class, $entity->repository);
    }

    public function testHashFormat(): void
    {
        $entity = new Entity(format: RedisFormat::HASH->value);

        $this->assertSame(RedisFormat::HASH->value, $entity->format);
        $this->assertInstanceOf(HashObjectConverter::class, $entity->converter);
        $this->assertInstanceOf(HashRepository::class, $entity->repository);
    }

    public function testCustomPrefix(): void
    {
        $entity = new Entity(prefix: 'custom_prefix');

        $this->assertSame('custom_prefix', $entity->prefix);
    }

    public function testTtl(): void
    {
        $entity = new Entity(ttl: 3600);

        $this->assertSame(3600, $entity->ttl);
    }

    public function testNullTtlByDefault(): void
    {
        $entity = new Entity();

        $this->assertNull($entity->ttl);
    }
}
