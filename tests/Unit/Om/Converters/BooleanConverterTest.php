<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Converters;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Om\Converters\BooleanConverter;

final class BooleanConverterTest extends TestCase
{
    private BooleanConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new BooleanConverter();
    }

    public function testConvertTrue(): void
    {
        $this->assertSame('true', $this->converter->convert(true));
    }

    public function testConvertFalse(): void
    {
        $this->assertSame('false', $this->converter->convert(false));
    }

    public function testConvertNull(): void
    {
        $this->assertNull($this->converter->convert(null));
    }

    public function testRevertTrue(): void
    {
        $this->assertTrue($this->converter->revert('true', 'bool'));
    }

    public function testRevertFalse(): void
    {
        $this->assertFalse($this->converter->revert('false', 'bool'));
    }

    public function testRevertUnknownValue(): void
    {
        $this->assertNull($this->converter->revert('unknown', 'bool'));
    }

    public function testSupportsConversionForBool(): void
    {
        $this->assertTrue($this->converter->supportsConversion('bool', true));
        $this->assertTrue($this->converter->supportsConversion('boolean', false));
    }

    public function testDoesNotSupportConversionForString(): void
    {
        $this->assertFalse($this->converter->supportsConversion('string', 'true'));
    }

    public function testSupportsReversionForBool(): void
    {
        $this->assertTrue($this->converter->supportsReversion('bool', 'true'));
    }

    public function testDoesNotSupportReversionForString(): void
    {
        $this->assertFalse($this->converter->supportsReversion('string', 'true'));
    }
}
