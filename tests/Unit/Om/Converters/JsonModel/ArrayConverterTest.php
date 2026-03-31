<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Converters\JsonModel;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Om\Converters\JsonModel\ArrayConverter;

final class ArrayConverterTest extends TestCase
{
    private ArrayConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new ArrayConverter();
    }

    public function testConvertSimpleArray(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $result = $this->converter->convert($data);

        $this->assertSame('value1', $result['key1']);
        $this->assertSame('value2', $result['key2']);
    }

    public function testConvertNestedArray(): void
    {
        $data = ['nested' => ['inner' => 'value']];
        $result = $this->converter->convert($data);

        $this->assertArrayHasKey('nested', $result);
        $this->assertIsArray($result['nested']);
    }

    public function testConvertArrayWithDateTimeValues(): void
    {
        $date = new \DateTime('2024-01-15');
        $data = ['createdAt' => $date];
        $result = $this->converter->convert($data);

        $this->assertArrayHasKey('createdAt', $result);
        $this->assertIsArray($result['createdAt']);
        $this->assertArrayHasKey('date', $result['createdAt']);
    }

    public function testRevertSimpleArray(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $result = $this->converter->revert($data, 'array');

        $this->assertSame('value1', $result['key1']);
        $this->assertSame('value2', $result['key2']);
    }

    public function testRevertWithTypedValue(): void
    {
        $data = [
            'date' => [
                '#type' => 'DateTime',
                'date' => '2024-01-15 00:00:00.000000',
                'timezone_type' => 3,
                'timezone' => 'UTC',
            ],
        ];
        $result = $this->converter->revert($data, 'array');

        $this->assertArrayHasKey('date', $result);
        $this->assertInstanceOf(\DateTime::class, $result['date']);
    }

    public function testConvertAndRevertRoundTripSimple(): void
    {
        $original = ['name' => 'John', 'city' => 'Paris'];
        $converted = $this->converter->convert($original);
        $reverted = $this->converter->revert($converted, 'array');

        $this->assertSame($original, $reverted);
    }

    public function testSupportsConversion(): void
    {
        $this->assertTrue($this->converter->supportsConversion('array', [1, 2]));
        $this->assertFalse($this->converter->supportsConversion('array', null));
        $this->assertFalse($this->converter->supportsConversion('string', 'test'));
    }

    public function testSupportsReversion(): void
    {
        $this->assertTrue($this->converter->supportsReversion('array', ['data']));
        $this->assertFalse($this->converter->supportsReversion('array', null));
        $this->assertFalse($this->converter->supportsReversion('string', ['data']));
    }
}
