<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om\Converters\HashModel;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Om\Converters\BooleanConverter;
use Talleu\RedisOm\Om\Converters\HashModel\ArrayConverter;
use Talleu\RedisOm\Om\Converters\HashModel\ConverterFactory;
use Talleu\RedisOm\Om\Converters\HashModel\DateTimeConverter;
use Talleu\RedisOm\Om\Converters\HashModel\DateTimeImmutableConverter;
use Talleu\RedisOm\Om\Converters\HashModel\HashObjectConverter;
use Talleu\RedisOm\Om\Converters\HashModel\NullConverter;
use Talleu\RedisOm\Om\Converters\ScalarConverter;
use Talleu\RedisOm\Om\Mapping as RedisOm;

final class ConverterFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        ConverterFactory::clearCache();
    }

    public function testGetConverterForInt(): void
    {
        $converter = ConverterFactory::getConverter('int', 42);
        $this->assertInstanceOf(ScalarConverter::class, $converter);
    }

    public function testGetConverterForString(): void
    {
        $converter = ConverterFactory::getConverter('string', 'hello');
        $this->assertInstanceOf(ScalarConverter::class, $converter);
    }

    public function testGetConverterForFloat(): void
    {
        $converter = ConverterFactory::getConverter('float', 3.14);
        $this->assertInstanceOf(ScalarConverter::class, $converter);
    }

    public function testGetConverterForBool(): void
    {
        $converter = ConverterFactory::getConverter('bool', true);
        $this->assertInstanceOf(BooleanConverter::class, $converter);
    }

    public function testGetConverterForNull(): void
    {
        $converter = ConverterFactory::getConverter('NULL', null);
        $this->assertInstanceOf(NullConverter::class, $converter);
    }

    public function testGetConverterForArray(): void
    {
        $converter = ConverterFactory::getConverter('array', [1, 2, 3]);
        $this->assertInstanceOf(ArrayConverter::class, $converter);
    }

    public function testGetConverterForDateTime(): void
    {
        $converter = ConverterFactory::getConverter('DateTime', new \DateTime());
        $this->assertInstanceOf(DateTimeConverter::class, $converter);
    }

    public function testGetConverterForDateTimeImmutable(): void
    {
        $converter = ConverterFactory::getConverter('DateTimeImmutable', new \DateTimeImmutable());
        $this->assertInstanceOf(DateTimeImmutableConverter::class, $converter);
    }

    public function testGetConverterForObject(): void
    {
        $object = new ConverterFactoryTestDummy();
        $converter = ConverterFactory::getConverter(ConverterFactoryTestDummy::class, $object);
        $this->assertInstanceOf(HashObjectConverter::class, $converter);
    }

    public function testGetReverterForInt(): void
    {
        $reverter = ConverterFactory::getReverter('int', '42');
        $this->assertInstanceOf(ScalarConverter::class, $reverter);
    }

    public function testGetReverterForBool(): void
    {
        $reverter = ConverterFactory::getReverter('bool', 'true');
        $this->assertInstanceOf(BooleanConverter::class, $reverter);
    }

    public function testGetReverterForNull(): void
    {
        $reverter = ConverterFactory::getReverter('string', null);
        $this->assertInstanceOf(NullConverter::class, $reverter);
    }

    public function testGetReverterForNullString(): void
    {
        $reverter = ConverterFactory::getReverter('string', 'null');
        $this->assertInstanceOf(NullConverter::class, $reverter);
    }

    public function testGetConverterReturnsNullConverterForNullData(): void
    {
        $converter = ConverterFactory::getConverter('resource', null);
        $this->assertInstanceOf(NullConverter::class, $converter);
    }
}

#[RedisOm\Entity]
class ConverterFactoryTestDummy
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?int $id = null;
}
