<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Converters;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Om\Converters\EnumConverter;
use Talleu\RedisOm\Om\Converters\HashModel\ConverterFactory as HashConverterFactory;
use Talleu\RedisOm\Om\Converters\JsonModel\ConverterFactory as JsonConverterFactory;

enum StatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}

enum PriorityEnum: int
{
    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;
}

final class EnumConverterTest extends TestCase
{
    private EnumConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new EnumConverter();
        HashConverterFactory::clearCache();
        JsonConverterFactory::clearCache();
    }

    public function testConvertStringEnum(): void
    {
        $result = $this->converter->convert(StatusEnum::ACTIVE);
        $this->assertSame('active', $result);
    }

    public function testConvertIntEnum(): void
    {
        $result = $this->converter->convert(PriorityEnum::HIGH);
        $this->assertSame(3, $result);
    }

    public function testRevertStringEnum(): void
    {
        $result = $this->converter->revert('inactive', StatusEnum::class);
        $this->assertSame(StatusEnum::INACTIVE, $result);
    }

    public function testRevertIntEnum(): void
    {
        $result = $this->converter->revert(2, PriorityEnum::class);
        $this->assertSame(PriorityEnum::MEDIUM, $result);
    }

    public function testRevertIntEnumFromString(): void
    {
        // Redis stores everything as strings in HASH model
        $result = $this->converter->revert('1', PriorityEnum::class);
        $this->assertSame(PriorityEnum::LOW, $result);
    }

    public function testRevertInvalidValueThrows(): void
    {
        $this->expectException(\ValueError::class);
        $this->converter->revert('nonexistent', StatusEnum::class);
    }

    public function testSupportsConversionForBackedEnum(): void
    {
        $this->assertTrue($this->converter->supportsConversion(StatusEnum::class, StatusEnum::ACTIVE));
        $this->assertTrue($this->converter->supportsConversion(PriorityEnum::class, PriorityEnum::HIGH));
    }

    public function testDoesNotSupportConversionForNonEnum(): void
    {
        $this->assertFalse($this->converter->supportsConversion('string', 'hello'));
        $this->assertFalse($this->converter->supportsConversion('int', 42));
        $this->assertFalse($this->converter->supportsConversion('bool', true));
    }

    public function testSupportsReversionForBackedEnum(): void
    {
        $this->assertTrue($this->converter->supportsReversion(StatusEnum::class, 'active'));
        $this->assertTrue($this->converter->supportsReversion(PriorityEnum::class, 1));
    }

    public function testDoesNotSupportReversionForNull(): void
    {
        $this->assertFalse($this->converter->supportsReversion(StatusEnum::class, null));
        $this->assertFalse($this->converter->supportsReversion(StatusEnum::class, 'null'));
    }

    public function testDoesNotSupportReversionForNonEnumType(): void
    {
        $this->assertFalse($this->converter->supportsReversion('string', 'active'));
        $this->assertFalse($this->converter->supportsReversion(\stdClass::class, 'test'));
    }

    public function testRoundTripStringEnum(): void
    {
        $original = StatusEnum::PENDING;
        $converted = $this->converter->convert($original);
        $reverted = $this->converter->revert($converted, StatusEnum::class);
        $this->assertSame($original, $reverted);
    }

    public function testRoundTripIntEnum(): void
    {
        $original = PriorityEnum::MEDIUM;
        $converted = $this->converter->convert($original);
        $reverted = $this->converter->revert($converted, PriorityEnum::class);
        $this->assertSame($original, $reverted);
    }

    public function testHashConverterFactoryFindsEnumConverter(): void
    {
        $converter = HashConverterFactory::getConverter(StatusEnum::class, StatusEnum::ACTIVE);
        $this->assertInstanceOf(EnumConverter::class, $converter);
    }

    public function testJsonConverterFactoryFindsEnumConverter(): void
    {
        $converter = JsonConverterFactory::getConverter(StatusEnum::class, StatusEnum::ACTIVE);
        $this->assertInstanceOf(EnumConverter::class, $converter);
    }

    public function testHashConverterFactoryFindsEnumReverter(): void
    {
        $reverter = HashConverterFactory::getReverter(StatusEnum::class, 'active');
        $this->assertInstanceOf(EnumConverter::class, $reverter);
    }

    public function testJsonConverterFactoryFindsEnumReverter(): void
    {
        $reverter = JsonConverterFactory::getReverter(StatusEnum::class, 'active');
        $this->assertInstanceOf(EnumConverter::class, $reverter);
    }
}
