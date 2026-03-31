<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Converters\JsonModel;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Om\Converters\JsonModel\DateTimeConverter;

final class DateTimeConverterTest extends TestCase
{
    private DateTimeConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new DateTimeConverter();
    }

    public function testConvertReturnsArrayWithRequiredKeys(): void
    {
        $date = new \DateTime('2024-01-15 10:30:00', new \DateTimeZone('UTC'));
        $result = $this->converter->convert($date);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('#type', $result);
        $this->assertSame('DateTime', $result['#type']);
    }

    public function testConvertTimestampIsCorrect(): void
    {
        $date = new \DateTime('2024-01-15 10:30:00', new \DateTimeZone('UTC'));
        $result = $this->converter->convert($date);

        $this->assertSame((string) $date->getTimestamp(), $result['timestamp']);
    }

    public function testRevert(): void
    {
        $data = [
            'date' => '2024-01-15 10:30:00.000000',
            'timezone_type' => 3,
            'timezone' => 'UTC',
        ];

        $result = $this->converter->revert($data, 'DateTime');

        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertSame('2024-01-15', $result->format('Y-m-d'));
        $this->assertSame('UTC', $result->getTimezone()->getName());
    }

    public function testRevertWithoutTimezone(): void
    {
        $data = [
            'date' => '2024-01-15 10:30:00.000000',
        ];

        $result = $this->converter->revert($data, 'DateTime');

        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertSame('2024-01-15', $result->format('Y-m-d'));
    }

    public function testConvertAndRevertRoundTrip(): void
    {
        $original = new \DateTime('2024-06-15 14:30:00', new \DateTimeZone('UTC'));
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
        $this->assertFalse($this->converter->supportsConversion('DateTimeImmutable', new \DateTimeImmutable()));
        $this->assertFalse($this->converter->supportsConversion('DateTime', null));
    }

    public function testSupportsReversion(): void
    {
        $this->assertTrue($this->converter->supportsReversion('DateTime', ['date' => '...']));
        $this->assertFalse($this->converter->supportsReversion('DateTimeImmutable', []));
    }
}
