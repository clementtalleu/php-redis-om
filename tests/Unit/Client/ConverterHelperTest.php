<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Client;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Client\Helper\Converter;

final class ConverterHelperTest extends TestCase
{
    public function testPrefixReplacesBackslashes(): void
    {
        $this->assertSame('App_Entity_User', Converter::prefix('App\\Entity\\User'));
    }

    public function testPrefixWithNoBackslashes(): void
    {
        $this->assertSame('simple_key', Converter::prefix('simple_key'));
    }

    public function testPrefixWithEmptyString(): void
    {
        $this->assertSame('', Converter::prefix(''));
    }

    public function testPrefixWithMultipleBackslashes(): void
    {
        $this->assertSame('A_B_C_D', Converter::prefix('A\\B\\C\\D'));
    }

    public function testPrefixWithLeadingBackslash(): void
    {
        $this->assertSame('_App_Entity', Converter::prefix('\\App\\Entity'));
    }
}
