<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Converters\HashModel;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Om\Converters\HashModel\ArrayConverter;

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
        $result = $this->converter->convert($data, [], 'parent');

        $this->assertSame('value1', $result['parent.key1']);
        $this->assertSame('value2', $result['parent.key2']);
    }

    public function testConvertNestedArray(): void
    {
        $data = ['nested' => ['inner' => 'value']];
        $result = $this->converter->convert($data, [], 'parent');

        $this->assertArrayHasKey('parent.nested.inner', $result);
        $this->assertSame('value', $result['parent.nested.inner']);
    }

    public function testConvertArrayWithIntValues(): void
    {
        $data = ['count' => 42];
        $result = $this->converter->convert($data, [], 'parent');

        $this->assertSame('42', $result['parent.count']);
        $this->assertSame('integer', $result['parent.count.#type']);
    }

    public function testConvertArrayWithBoolValues(): void
    {
        $data = ['active' => true];
        $result = $this->converter->convert($data, [], 'parent');

        $this->assertSame('true', $result['parent.active']);
    }

    public function testConvertArrayWithDateTimeValues(): void
    {
        $date = new \DateTime('2024-01-15');
        $data = ['createdAt' => $date];
        $result = $this->converter->convert($data, [], 'parent');

        $this->assertArrayHasKey('parent.createdAt', $result);
        $this->assertArrayHasKey('parent.createdAt#timestamp', $result);
    }

    public function testRevertSimpleArray(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $result = $this->converter->revert($data, 'array');

        $this->assertSame('value1', $result['key1']);
        $this->assertSame('value2', $result['key2']);
    }

    public function testRevertSkipsTimestampKeys(): void
    {
        $data = [
            'date' => '2024-01-15',
            'date#timestamp' => '1705276800',
        ];
        $result = $this->converter->revert($data, 'array');

        $this->assertArrayHasKey('date', $result);
        $this->assertArrayNotHasKey('date#timestamp', $result);
    }

    public function testSupportsConversion(): void
    {
        $this->assertTrue($this->converter->supportsConversion('array', [1, 2]));
        $this->assertTrue($this->converter->supportsConversion('iterable', [1]));
        $this->assertFalse($this->converter->supportsConversion('array', null));
        $this->assertFalse($this->converter->supportsConversion('string', 'test'));
    }

    public function testSupportsReversion(): void
    {
        $this->assertTrue($this->converter->supportsReversion('array', ['data']));
        $this->assertFalse($this->converter->supportsReversion('array', null));
        $this->assertFalse($this->converter->supportsReversion('array', 'null'));
        $this->assertFalse($this->converter->supportsReversion('string', ['data']));
    }
}
