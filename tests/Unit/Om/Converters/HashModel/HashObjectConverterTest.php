<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Converters\HashModel;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Om\Converters\HashModel\HashObjectConverter;
use Talleu\RedisOm\Om\Mapping as RedisOm;

final class HashObjectConverterTest extends TestCase
{
    private HashObjectConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new HashObjectConverter();
    }

    public function testConvertSimpleObject(): void
    {
        $object = new HashConverterTestSimple();
        $object->id = 1;
        $object->name = 'John';
        $object->age = 30;

        $result = $this->converter->convert($object);

        $this->assertSame('1', $result['id']);
        $this->assertSame('John', $result['name']);
        $this->assertSame('30', $result['age']);
    }

    public function testConvertObjectWithBooleans(): void
    {
        $object = new HashConverterTestWithBool();
        $object->id = 1;
        $object->active = true;

        $result = $this->converter->convert($object);

        $this->assertSame('true', $result['active']);
    }

    public function testConvertObjectWithNullValue(): void
    {
        $object = new HashConverterTestWithNull();
        $object->id = 1;
        $object->name = null;

        $result = $this->converter->convert($object);

        $this->assertSame('null', $result['name']);
    }

    public function testConvertObjectWithDateTime(): void
    {
        $object = new HashConverterTestWithDate();
        $object->id = 1;
        $object->createdAt = new \DateTime('2024-01-15 10:30:00');

        $result = $this->converter->convert($object);

        $this->assertArrayHasKey('createdAt', $result);
        $this->assertArrayHasKey('createdAt#timestamp', $result);
        $this->assertIsNumeric($result['createdAt#timestamp']);
    }

    public function testConvertObjectWithNestedObject(): void
    {
        $nested = new HashConverterTestNested();
        $nested->id = 2;
        $nested->title = 'Nested';

        $object = new HashConverterTestWithNested();
        $object->id = 1;
        $object->nested = $nested;

        $result = $this->converter->convert($object);

        $this->assertSame('2', $result['nested.id']);
        $this->assertSame('Nested', $result['nested.title']);
    }

    public function testConvertSkipsPropertiesWithoutAttribute(): void
    {
        $object = new HashConverterTestPartial();
        $object->id = 1;
        $object->mapped = 'mapped';
        $object->unmapped = 'unmapped';

        $result = $this->converter->convert($object);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('mapped', $result);
        $this->assertArrayNotHasKey('unmapped', $result);
    }

    public function testRevertSimpleObject(): void
    {
        $data = [
            'id' => '1',
            'name' => 'John',
            'age' => '30',
        ];

        $result = $this->converter->revert($data, HashConverterTestSimple::class);

        $this->assertInstanceOf(HashConverterTestSimple::class, $result);
        $this->assertSame(1, $result->id);
        $this->assertSame('John', $result->name);
        $this->assertSame(30, $result->age);
    }

    public function testRevertObjectWithBoolean(): void
    {
        $data = [
            'id' => '1',
            'active' => 'true',
        ];

        $result = $this->converter->revert($data, HashConverterTestWithBool::class);

        $this->assertTrue($result->active);
    }

    public function testRevertObjectWithNull(): void
    {
        $data = [
            'id' => '1',
            'name' => 'null',
        ];

        $result = $this->converter->revert($data, HashConverterTestWithNull::class);

        $this->assertNull($result->name);
    }

    public function testRevertIgnoresUnknownProperties(): void
    {
        $data = [
            'id' => '1',
            'name' => 'John',
            'unknown_field' => 'should be ignored',
        ];

        $result = $this->converter->revert($data, HashConverterTestSimple::class);

        $this->assertSame(1, $result->id);
        $this->assertSame('John', $result->name);
    }

    public function testSupportsConversion(): void
    {
        $this->assertTrue($this->converter->supportsConversion(HashConverterTestSimple::class, new HashConverterTestSimple()));
        $this->assertFalse($this->converter->supportsConversion('string', 'hello'));
        $this->assertFalse($this->converter->supportsConversion(\stdClass::class, new \stdClass()));
        $this->assertFalse($this->converter->supportsConversion(HashConverterTestSimple::class, null));
    }

    public function testSupportsReversion(): void
    {
        $this->assertTrue($this->converter->supportsReversion(HashConverterTestSimple::class, ['id' => '1']));
        $this->assertFalse($this->converter->supportsReversion('string', 'hello'));
        $this->assertFalse($this->converter->supportsReversion(\stdClass::class, []));
        $this->assertFalse($this->converter->supportsReversion(HashConverterTestSimple::class, 'null'));
    }

    public function testConvertAndRevertRoundTrip(): void
    {
        $object = new HashConverterTestSimple();
        $object->id = 42;
        $object->name = 'Round Trip';
        $object->age = 25;

        $converted = $this->converter->convert($object);
        $reverted = $this->converter->revert($converted, HashConverterTestSimple::class);

        $this->assertEquals($object, $reverted);
    }
}

#[RedisOm\Entity]
class HashConverterTestSimple
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public string $name = '';

    #[RedisOm\Property]
    public int $age = 0;
}

#[RedisOm\Entity]
class HashConverterTestWithBool
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public bool $active = false;
}

#[RedisOm\Entity]
class HashConverterTestWithNull
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public ?string $name = null;
}

#[RedisOm\Entity]
class HashConverterTestWithDate
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public ?\DateTime $createdAt = null;
}

#[RedisOm\Entity]
class HashConverterTestNested
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public string $title = '';
}

#[RedisOm\Entity]
class HashConverterTestWithNested
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public ?HashConverterTestNested $nested = null;
}

#[RedisOm\Entity]
class HashConverterTestPartial
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public string $mapped = '';

    public string $unmapped = '';
}
