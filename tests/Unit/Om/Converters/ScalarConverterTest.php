<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Converters;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Om\Converters\ScalarConverter;

final class ScalarConverterTest extends TestCase
{
    private ScalarConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new ScalarConverter();
    }

    public function testConvertString(): void
    {
        $this->assertSame('hello', $this->converter->convert('hello'));
    }

    public function testConvertInt(): void
    {
        $this->assertSame('42', $this->converter->convert(42));
    }

    public function testConvertFloat(): void
    {
        $this->assertSame('3.14', $this->converter->convert(3.14));
    }

    public function testConvertZero(): void
    {
        $this->assertSame('0', $this->converter->convert(0));
    }

    public function testConvertEmptyString(): void
    {
        $this->assertSame('', $this->converter->convert(''));
    }

    public function testRevertToInt(): void
    {
        $this->assertSame(42, $this->converter->revert('42', 'int'));
    }

    public function testRevertToFloat(): void
    {
        $this->assertSame(3.14, $this->converter->revert('3.14', 'float'));
    }

    public function testRevertToString(): void
    {
        $this->assertSame('hello', $this->converter->revert('hello', 'string'));
    }

    public function testRevertZeroToInt(): void
    {
        $this->assertSame(0, $this->converter->revert('0', 'int'));
    }

    public function testRevertZeroToFloat(): void
    {
        $this->assertSame(0.0, $this->converter->revert('0', 'float'));
    }

    public function testSupportsConversionForInt(): void
    {
        $this->assertTrue($this->converter->supportsConversion('int', 42));
    }

    public function testSupportsConversionForString(): void
    {
        $this->assertTrue($this->converter->supportsConversion('string', 'hello'));
    }

    public function testSupportsConversionForFloat(): void
    {
        $this->assertTrue($this->converter->supportsConversion('float', 3.14));
    }

    public function testSupportsConversionForDouble(): void
    {
        $this->assertTrue($this->converter->supportsConversion('double', 3.14));
    }

    public function testDoesNotSupportConversionForNull(): void
    {
        $this->assertFalse($this->converter->supportsConversion('string', null));
    }

    public function testDoesNotSupportConversionForBool(): void
    {
        $this->assertFalse($this->converter->supportsConversion('bool', true));
    }

    public function testDoesNotSupportConversionForArray(): void
    {
        $this->assertFalse($this->converter->supportsConversion('array', []));
    }

    public function testSupportsReversionForInt(): void
    {
        $this->assertTrue($this->converter->supportsReversion('int', '42'));
    }

    public function testSupportsReversionForString(): void
    {
        $this->assertTrue($this->converter->supportsReversion('string', 'hello'));
    }

    public function testDoesNotSupportReversionForNull(): void
    {
        $this->assertFalse($this->converter->supportsReversion('string', null));
    }

    public function testDoesNotSupportReversionForNullString(): void
    {
        $this->assertFalse($this->converter->supportsReversion('string', 'null'));
    }
}
