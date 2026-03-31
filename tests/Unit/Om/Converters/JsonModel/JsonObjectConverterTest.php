<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Converters\JsonModel;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Om\Converters\JsonModel\JsonObjectConverter;
use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Om\RedisFormat;

final class JsonObjectConverterTest extends TestCase
{
    private JsonObjectConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new JsonObjectConverter();
    }

    public function testConvertSimpleObject(): void
    {
        $object = new JsonConverterTestSimple();
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
        $object = new JsonConverterTestWithBool();
        $object->id = 1;
        $object->active = true;

        $result = $this->converter->convert($object);

        $this->assertSame('true', $result['active']);
    }

    public function testConvertObjectWithNull(): void
    {
        $object = new JsonConverterTestWithNull();
        $object->id = 1;
        $object->name = null;

        $result = $this->converter->convert($object);

        $this->assertArrayHasKey('name', $result);
    }

    public function testConvertObjectWithDateTime(): void
    {
        $object = new JsonConverterTestWithDate();
        $object->id = 1;
        $object->createdAt = new \DateTime('2024-01-15 10:30:00');

        $result = $this->converter->convert($object);

        $this->assertArrayHasKey('createdAt', $result);
        $this->assertIsArray($result['createdAt']);
        $this->assertArrayHasKey('date', $result['createdAt']);
        $this->assertArrayHasKey('timestamp', $result['createdAt']);
        $this->assertArrayHasKey('#type', $result['createdAt']);
    }

    public function testConvertObjectWithNestedObject(): void
    {
        $nested = new JsonConverterTestNested();
        $nested->id = 2;
        $nested->title = 'Nested';

        $object = new JsonConverterTestWithNested();
        $object->id = 1;
        $object->nested = $nested;

        $result = $this->converter->convert($object);

        $this->assertArrayHasKey('nested', $result);
        $this->assertIsArray($result['nested']);
        $this->assertArrayHasKey('#type', $result['nested']);
    }

    public function testConvertSkipsPropertiesWithoutAttribute(): void
    {
        $object = new JsonConverterTestPartial();
        $object->id = 1;
        $object->mapped = 'yes';
        $object->unmapped = 'no';

        $result = $this->converter->convert($object);

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

        $result = $this->converter->revert($data, JsonConverterTestSimple::class);

        $this->assertInstanceOf(JsonConverterTestSimple::class, $result);
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

        $result = $this->converter->revert($data, JsonConverterTestWithBool::class);

        $this->assertTrue($result->active);
    }

    public function testRevertObjectWithNull(): void
    {
        $data = [
            'id' => '1',
            'name' => null,
        ];

        $result = $this->converter->revert($data, JsonConverterTestWithNull::class);

        $this->assertNull($result->name);
    }

    public function testRevertIgnoresUnknownProperties(): void
    {
        $data = [
            'id' => '1',
            'name' => 'John',
            'unknown_field' => 'ignored',
        ];

        $result = $this->converter->revert($data, JsonConverterTestSimple::class);

        $this->assertSame(1, $result->id);
        $this->assertSame('John', $result->name);
    }

    public function testConvertAndRevertRoundTrip(): void
    {
        $object = new JsonConverterTestSimple();
        $object->id = 42;
        $object->name = 'Round Trip';
        $object->age = 25;

        $converted = $this->converter->convert($object);
        $reverted = $this->converter->revert($converted, JsonConverterTestSimple::class);

        $this->assertEquals($object, $reverted);
    }

    public function testRevertStripsTypeMetadataFromArrays(): void
    {
        $data = [
            'id' => '1',
            'tags' => [
                'foo', 'bar',
                '#type' => 'array',
            ],
        ];

        $result = $this->converter->revert($data, JsonConverterTestWithArray::class);

        $this->assertIsArray($result->tags);
        $this->assertArrayNotHasKey('#type', $result->tags);
        $this->assertContains('foo', $result->tags);
        $this->assertContains('bar', $result->tags);
    }

    public function testConvertAndRevertRoundTripWithArrays(): void
    {
        $object = new JsonConverterTestWithArray();
        $object->id = 1;
        $object->tags = ['php', 'redis', 'orm'];

        $converted = $this->converter->convert($object);
        // convert() should add #type for internal use
        $this->assertArrayHasKey('#type', $converted['tags']);

        $reverted = $this->converter->revert($converted, JsonConverterTestWithArray::class);
        // revert() should strip #type - user data stays clean
        $this->assertEquals($object, $reverted);
        $this->assertArrayNotHasKey('#type', $reverted->tags);
    }

    public function testConvertAndRevertRoundTripWithNestedObject(): void
    {
        $nested = new JsonConverterTestNested();
        $nested->id = 5;
        $nested->title = 'Hello';

        $object = new JsonConverterTestWithNested();
        $object->id = 1;
        $object->nested = $nested;

        $converted = $this->converter->convert($object);
        $reverted = $this->converter->revert($converted, JsonConverterTestWithNested::class);

        $this->assertEquals($object, $reverted);
    }

    public function testSupportsConversion(): void
    {
        $this->assertTrue($this->converter->supportsConversion(JsonConverterTestSimple::class, new JsonConverterTestSimple()));
        $this->assertFalse($this->converter->supportsConversion('string', 'hello'));
        $this->assertFalse($this->converter->supportsConversion(\stdClass::class, new \stdClass()));
    }

    public function testSupportsReversion(): void
    {
        $this->assertTrue($this->converter->supportsReversion(JsonConverterTestSimple::class, ['id' => '1']));
        $this->assertFalse($this->converter->supportsReversion('string', 'hello'));
        $this->assertFalse($this->converter->supportsReversion(JsonConverterTestSimple::class, null));
        $this->assertFalse($this->converter->supportsReversion(JsonConverterTestSimple::class, 'null'));
    }
}

#[RedisOm\Entity(format: RedisFormat::JSON->value)]
class JsonConverterTestSimple
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public string $name = '';

    #[RedisOm\Property]
    public int $age = 0;
}

#[RedisOm\Entity(format: RedisFormat::JSON->value)]
class JsonConverterTestWithBool
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public bool $active = false;
}

#[RedisOm\Entity(format: RedisFormat::JSON->value)]
class JsonConverterTestWithNull
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public ?string $name = null;
}

#[RedisOm\Entity(format: RedisFormat::JSON->value)]
class JsonConverterTestWithDate
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public ?\DateTime $createdAt = null;
}

#[RedisOm\Entity(format: RedisFormat::JSON->value)]
class JsonConverterTestNested
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public string $title = '';
}

#[RedisOm\Entity(format: RedisFormat::JSON->value)]
class JsonConverterTestWithNested
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public ?JsonConverterTestNested $nested = null;
}

#[RedisOm\Entity(format: RedisFormat::JSON->value)]
class JsonConverterTestWithArray
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public array $tags = [];
}

#[RedisOm\Entity(format: RedisFormat::JSON->value)]
class JsonConverterTestPartial
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;

    #[RedisOm\Property]
    public string $mapped = '';

    public string $unmapped = '';
}
