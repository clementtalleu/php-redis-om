<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Om;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Om\RedisFormat;

final class RedisFormatTest extends TestCase
{
    public function testHashFormat(): void
    {
        $this->assertSame('HASH', RedisFormat::HASH->value);
    }

    public function testJsonFormat(): void
    {
        $this->assertSame('JSON', RedisFormat::JSON->value);
    }

    public function testAllCases(): void
    {
        $cases = RedisFormat::cases();
        $this->assertCount(2, $cases);
    }
}
