<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Key;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Exception\BadIdentifierConfigurationException;
use Talleu\RedisOm\Om\Key\KeyGenerator;
use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\Mapping\Entity;

final class KeyGeneratorTest extends TestCase
{
    private KeyGenerator $keyGenerator;

    protected function setUp(): void
    {
        $this->keyGenerator = new KeyGenerator();
    }

    public function testGenerateKeyWithExistingId(): void
    {
        $entity = new Entity();
        $object = new KeyGeneratorTestDummy();
        $object->id = 42;

        $key = $this->keyGenerator->generateKey($entity, $object);

        $this->assertSame(KeyGeneratorTestDummy::class . ':42', $key);
    }

    public function testGenerateKeyWithPrefix(): void
    {
        $entity = new Entity(prefix: 'my_prefix');
        $object = new KeyGeneratorTestDummy();
        $object->id = 1;

        $key = $this->keyGenerator->generateKey($entity, $object);

        $this->assertSame('my_prefix:1', $key);
    }

    public function testGenerateKeyWithNullIdGeneratesUniqueId(): void
    {
        $entity = new Entity();
        $object = new KeyGeneratorTestDummy();
        $object->id = null;

        $key = $this->keyGenerator->generateKey($entity, $object);

        $this->assertNotNull($object->id);
        $this->assertIsInt($object->id);
        $this->assertStringContainsString((string) $object->id, $key);
    }

    public function testGenerateKeyWithStringId(): void
    {
        $entity = new Entity();
        $object = new KeyGeneratorTestStringIdDummy();
        $object->id = 'my-uuid-123';

        $key = $this->keyGenerator->generateKey($entity, $object);

        $this->assertSame(KeyGeneratorTestStringIdDummy::class . ':my-uuid-123', $key);
    }

    public function testGenerateKeyWithNullStringIdGeneratesUniqueId(): void
    {
        $entity = new Entity();
        $object = new KeyGeneratorTestStringIdDummy();

        $key = $this->keyGenerator->generateKey($entity, $object);

        $this->assertNotNull($object->id);
        $this->assertIsString($object->id);
    }

    public function testGenerateKeyWithZeroIdDoesNotRegenerate(): void
    {
        $entity = new Entity();
        $object = new KeyGeneratorTestDummy();
        $object->id = 0;

        $key = $this->keyGenerator->generateKey($entity, $object);

        $this->assertSame(0, $object->id);
        $this->assertSame(KeyGeneratorTestDummy::class . ':0', $key);
    }

    public function testGetIdentifierWithIdAttribute(): void
    {
        $reflection = new \ReflectionClass(KeyGeneratorTestDummy::class);
        $property = $this->keyGenerator->getIdentifier($reflection);

        $this->assertSame('id', $property->getName());
    }

    public function testGetIdentifierWithCustomIdAttribute(): void
    {
        $reflection = new \ReflectionClass(KeyGeneratorTestCustomIdDummy::class);
        $property = $this->keyGenerator->getIdentifier($reflection);

        $this->assertSame('uuid', $property->getName());
    }

    public function testGetIdentifierFallsBackToIdProperty(): void
    {
        $reflection = new \ReflectionClass(KeyGeneratorTestNoAttributeDummy::class);
        $property = $this->keyGenerator->getIdentifier($reflection);

        $this->assertSame('id', $property->getName());
    }

    public function testGetIdentifierThrowsWhenNoIdProperty(): void
    {
        $this->expectException(BadIdentifierConfigurationException::class);

        $reflection = new \ReflectionClass(KeyGeneratorTestNoIdDummy::class);
        $this->keyGenerator->getIdentifier($reflection);
    }

    public function testGetIdentifierThrowsWhenIdIsPrivate(): void
    {
        $this->expectException(BadIdentifierConfigurationException::class);

        $reflection = new \ReflectionClass(KeyGeneratorTestPrivateIdDummy::class);
        $this->keyGenerator->getIdentifier($reflection);
    }
}

#[RedisOm\Entity]
class KeyGeneratorTestDummy
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public string $name = 'test';
}

#[RedisOm\Entity]
class KeyGeneratorTestStringIdDummy
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?string $id = null;

    #[RedisOm\Property]
    public string $name = 'test';
}

#[RedisOm\Entity]
class KeyGeneratorTestCustomIdDummy
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?string $uuid = null;

    #[RedisOm\Property]
    public string $name = 'test';
}

class KeyGeneratorTestNoAttributeDummy
{
    public ?int $id = null;
    public string $name = 'test';
}

class KeyGeneratorTestNoIdDummy
{
    public string $name = 'test';
}

class KeyGeneratorTestPrivateIdDummy
{
    #[RedisOm\Id]
    private ?int $id = null;
}
