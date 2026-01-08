<?php declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Event;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Event\LifecycleEventArgs;
use Talleu\RedisOm\Om\RedisObjectManagerInterface;

final class LifecycleEventArgsTest extends TestCase
{
    public function testGetObject(): void
    {
        $object = new \stdClass();
        $objectManager = $this->createMock(RedisObjectManagerInterface::class);

        $eventArgs = new LifecycleEventArgs($object, $objectManager);

        $this->assertSame($object, $eventArgs->getObject());
    }

    public function testGetObjectManager(): void
    {
        $object = new \stdClass();
        $objectManager = $this->createMock(RedisObjectManagerInterface::class);

        $eventArgs = new LifecycleEventArgs($object, $objectManager);

        $this->assertSame($objectManager, $eventArgs->getObjectManager());
    }
}
