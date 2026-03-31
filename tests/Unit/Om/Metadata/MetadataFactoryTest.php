<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Metadata;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\Metadata\ClassMetadata;
use Talleu\RedisOm\Om\Metadata\MetadataFactory;
use Talleu\RedisOm\Tests\Fixtures\Bar;
use Talleu\RedisOm\Tests\Fixtures\Hash\DummyHash;

final class MetadataFactoryTest extends TestCase
{
    private MetadataFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new MetadataFactory();
    }

    public function testCreateClassMetadataReturnsCorrectType(): void
    {
        $metadata = $this->factory->createClassMetadata(DummyHash::class);

        $this->assertInstanceOf(ClassMetadata::class, $metadata);
    }

    public function testClassMetadataName(): void
    {
        $metadata = $this->factory->createClassMetadata(DummyHash::class);

        $this->assertSame(DummyHash::class, $metadata->getName());
    }

    public function testClassMetadataIdentifier(): void
    {
        $metadata = $this->factory->createClassMetadata(DummyHash::class);

        $this->assertContains('id', $metadata->getIdentifier());
    }

    public function testClassMetadataFields(): void
    {
        $metadata = $this->factory->createClassMetadata(DummyHash::class);

        $fields = $metadata->getFieldNames();
        $this->assertContains('id', $fields);
        $this->assertContains('name', $fields);
        $this->assertContains('age', $fields);
        $this->assertContains('price', $fields);
    }

    public function testIsIdentifier(): void
    {
        $metadata = $this->factory->createClassMetadata(DummyHash::class);

        $this->assertTrue($metadata->isIdentifier('id'));
        $this->assertFalse($metadata->isIdentifier('name'));
    }

    public function testHasField(): void
    {
        $metadata = $this->factory->createClassMetadata(DummyHash::class);

        $this->assertTrue($metadata->hasField('name'));
        $this->assertTrue($metadata->hasField('age'));
        $this->assertFalse($metadata->hasField('nonExistent'));
    }

    public function testGetTypeOfField(): void
    {
        $metadata = $this->factory->createClassMetadata(DummyHash::class);

        $this->assertSame('int', $metadata->getTypeOfField('id'));
        $this->assertSame('string', $metadata->getTypeOfField('name'));
        $this->assertNull($metadata->getTypeOfField('nonExistent'));
    }

    public function testGetReflectionClass(): void
    {
        $metadata = $this->factory->createClassMetadata(DummyHash::class);

        $reflection = $metadata->getReflectionClass();
        $this->assertSame(DummyHash::class, $reflection->getName());
    }

    public function testIdentifierFieldNames(): void
    {
        $metadata = $this->factory->createClassMetadata(DummyHash::class);

        $this->assertContains('id', $metadata->getIdentifierFieldNames());
    }
}
