<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Converters\HashModel;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Om\Converters\AbstractDateTimeConverter;
use Talleu\RedisOm\Om\Converters\HashModel\DateTimeConverter;

final class DateTimeConverterTest extends TestCase
{
    private DateTimeConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new DateTimeConverter();
    }

    public function testConvert(): void
    {
        $date = new \DateTime('2024-01-15 10:30:00');
        $result = $this->converter->convert($date);

        $this->assertSame($date->format(AbstractDateTimeConverter::FORMAT), $result);
    }

    public function testRevertFromString(): void
    {
        $dateString = '2024-01-15T10:30:00.000000+00:00';
        $result = $this->converter->revert($dateString, 'DateTime');

        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertSame('2024-01-15', $result->format('Y-m-d'));
    }

    public function testRevertFromArray(): void
    {
        $data = ['2024-01-15T10:30:00.000000+00:00', 'extra'];
        $result = $this->converter->revert($data, 'DateTime');

        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertSame('2024-01-15', $result->format('Y-m-d'));
    }

    public function testConvertAndRevertRoundTrip(): void
    {
        $original = new \DateTime('2024-06-15 14:30:00');
        $converted = $this->converter->convert($original);
        $reverted = $this->converter->revert($converted, 'DateTime');

        $this->assertSame(
            $original->format('Y-m-d H:i:s'),
            $reverted->format('Y-m-d H:i:s')
        );
    }

    public function testSupportsConversion(): void
    {
        $this->assertTrue($this->converter->supportsConversion('DateTime', new \DateTime()));
        $this->assertTrue($this->converter->supportsConversion('DateTimeInterface', new \DateTime()));
        $this->assertFalse($this->converter->supportsConversion('DateTimeImmutable', new \DateTimeImmutable()));
        $this->assertFalse($this->converter->supportsConversion('string', 'test'));
        $this->assertFalse($this->converter->supportsConversion('DateTime', null));
    }

    public function testSupportsReversion(): void
    {
        $this->assertTrue($this->converter->supportsReversion('DateTime', 'some date'));
        $this->assertTrue($this->converter->supportsReversion('DateTimeInterface', 'some date'));
        $this->assertFalse($this->converter->supportsReversion('DateTimeImmutable', 'some date'));
        $this->assertFalse($this->converter->supportsReversion('string', 'some date'));
    }
}
