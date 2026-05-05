<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\ApiPlatform\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\ApiPlatform\State\RedisProcessor;
use Talleu\RedisOm\Om\RedisObjectManagerInterface;

final class RedisProcessorTest extends TestCase
{
    private RedisObjectManagerInterface $om;
    private RedisProcessor $processor;

    protected function setUp(): void
    {
        $this->om = $this->createMock(RedisObjectManagerInterface::class);
        $this->processor = new RedisProcessor($this->om);
    }

    public function testPostCallsPersist(): void
    {
        $data = new \stdClass();

        $this->om->expects($this->once())->method('persist')->with($data);
        $this->om->expects($this->never())->method('merge');
        $this->om->expects($this->once())->method('flush');

        $result = $this->processor->process($data, new Post());

        $this->assertSame($data, $result);
    }

    public function testPatchCallsMerge(): void
    {
        $data = new \stdClass();

        $this->om->expects($this->once())->method('merge')->with($data);
        $this->om->expects($this->never())->method('persist');
        $this->om->expects($this->once())->method('flush');

        $result = $this->processor->process($data, new Patch());

        $this->assertSame($data, $result);
    }

    public function testPutCallsMerge(): void
    {
        $data = new \stdClass();

        $this->om->expects($this->once())->method('merge')->with($data);
        $this->om->expects($this->never())->method('persist');
        $this->om->expects($this->once())->method('flush');

        $result = $this->processor->process($data, new Put());

        $this->assertSame($data, $result);
    }

    public function testDeleteCallsRemove(): void
    {
        $data = new \stdClass();

        $this->om->expects($this->once())->method('remove')->with($data);
        $this->om->expects($this->never())->method('persist');
        $this->om->expects($this->never())->method('merge');
        $this->om->expects($this->once())->method('flush');

        $result = $this->processor->process($data, new Delete());

        $this->assertNull($result);
    }

    public function testNonObjectDataReturnsEarlyWithNoManagerCalls(): void
    {
        $this->om->expects($this->never())->method('persist');
        $this->om->expects($this->never())->method('merge');
        $this->om->expects($this->never())->method('remove');
        $this->om->expects($this->never())->method('flush');

        $result = $this->processor->process('not-an-object', new Post());

        $this->assertSame('not-an-object', $result);
    }
}
