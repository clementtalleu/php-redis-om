<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Mapping;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Om\Mapping\Property;

final class PropertyAttributeTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $property = new Property();

        $this->assertFalse($property->index);
        $this->assertNull($property->getter);
        $this->assertNull($property->setter);
    }

    public function testIndexTrue(): void
    {
        $property = new Property(index: true);

        $this->assertTrue($property->index);
    }

    public function testIndexNull(): void
    {
        $property = new Property(index: null);

        $this->assertNull($property->index);
    }

    public function testIndexWithSingleStringType(): void
    {
        $property = new Property(index: 'TAG');

        $this->assertSame(['TAG'], $property->index);
    }

    public function testIndexWithArrayOfTypes(): void
    {
        $property = new Property(index: ['TAG', 'TEXT']);

        $this->assertSame(['TAG', 'TEXT'], $property->index);
    }

    public function testIndexWithInvalidTypeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Property(index: ['INVALID_TYPE']);
    }

    public function testAllValidIndexTypes(): void
    {
        foreach (Property::INDEX_TYPES as $type) {
            $property = new Property(index: [$type]);
            $this->assertContains($type, $property->index);
        }
    }

    public function testGetterAndSetter(): void
    {
        $property = new Property(getter: 'getName', setter: 'setName');

        $this->assertSame('getName', $property->getter);
        $this->assertSame('setName', $property->setter);
    }
}
