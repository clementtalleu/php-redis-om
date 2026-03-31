<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Unit\Event;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Event\EventArgs;
use Talleu\RedisOm\Event\EventManager;
use Talleu\RedisOm\Event\EventSubscriberInterface;

final class EventManagerTest extends TestCase
{
    private EventManager $eventManager;

    protected function setUp(): void
    {
        $this->eventManager = new EventManager();
    }

    public function testDispatchEventWithNoListeners(): void
    {
        $this->eventManager->dispatchEvent('nonExistentEvent');
        $this->assertFalse($this->eventManager->hasListeners('nonExistentEvent'));
    }

    public function testAddAndDispatchListener(): void
    {
        $called = false;
        $listener = new class($called) {
            public function __construct(private bool &$called) {}
            public function onTest(EventArgs $args): void
            {
                $this->called = true;
            }
        };

        $this->eventManager->addEventListener('onTest', $listener);
        $this->assertTrue($this->eventManager->hasListeners('onTest'));

        $this->eventManager->dispatchEvent('onTest');
        $this->assertTrue($called);
    }

    public function testRemoveEventListener(): void
    {
        $listener = new class {
            public function onTest(): void {}
        };

        $this->eventManager->addEventListener('onTest', $listener);
        $this->assertTrue($this->eventManager->hasListeners('onTest'));

        $this->eventManager->removeEventListener('onTest', $listener);
        $this->assertEmpty($this->eventManager->getListeners('onTest'));
    }

    public function testAddMultipleEvents(): void
    {
        $listener = new class {
            public function event1(): void {}
            public function event2(): void {}
        };

        $this->eventManager->addEventListener(['event1', 'event2'], $listener);

        $this->assertTrue($this->eventManager->hasListeners('event1'));
        $this->assertTrue($this->eventManager->hasListeners('event2'));
    }

    public function testGetListenersForNonExistentEvent(): void
    {
        $this->assertEmpty($this->eventManager->getListeners('nonExistent'));
    }

    public function testRemoveEventSubscriber(): void
    {
        $subscriber = new class implements EventSubscriberInterface {
            public function getSubscribedEvents(): array
            {
                return ['event1' => 'onEvent1'];
            }
            public function event1(): void {}
        };

        $this->eventManager->addEventSubscriber($subscriber);
        $this->assertTrue($this->eventManager->hasListeners('event1'));

        $this->eventManager->removeEventSubscriber($subscriber);
        $this->assertEmpty($this->eventManager->getListeners('event1'));
    }

    public function testDispatchWithCallableListener(): void
    {
        $called = false;
        $listener = new class($called) {
            public function __construct(private bool &$called) {}
            public function __invoke(EventArgs $args): void
            {
                $this->called = true;
            }
        };

        $this->eventManager->addEventListener('test', $listener);
        $this->eventManager->dispatchEvent('test');
        $this->assertTrue($called);
    }

    public function testMultipleListenersOnSameEvent(): void
    {
        $count = 0;
        $listener1 = new class($count) {
            public function __construct(private int &$count) {}
            public function onEvent(EventArgs $args): void { $this->count++; }
        };
        $listener2 = new class($count) {
            public function __construct(private int &$count) {}
            public function onEvent(EventArgs $args): void { $this->count++; }
        };

        $this->eventManager->addEventListener('onEvent', $listener1);
        $this->eventManager->addEventListener('onEvent', $listener2);

        $this->eventManager->dispatchEvent('onEvent');
        $this->assertSame(2, $count);
    }

    public function testRemoveListenerForNonExistentEvent(): void
    {
        $listener = new class {};

        // Should not throw
        $this->eventManager->removeEventListener('nonExistent', $listener);
        $this->assertFalse($this->eventManager->hasListeners('nonExistent'));
    }
}
