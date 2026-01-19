<?php declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Event;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Event\EventArgs;
use Talleu\RedisOm\Event\EventManager;
use Talleu\RedisOm\Event\Events;
use Talleu\RedisOm\Event\EventSubscriberInterface;

final class EventManagerTest extends TestCase
{
    private EventManager $eventManager;

    protected function setUp(): void
    {
        $this->eventManager = new EventManager();
    }

    public function testAddEventListener(): void
    {
        $listener = new class {
            public bool $called = false;

            public function prePersist(EventArgs $args): void
            {
                $this->called = true;
            }
        };
        $this->eventManager->addEventListener(Events::PRE_PERSIST, $listener);
        $this->eventManager->dispatchEvent(Events::PRE_PERSIST);

        $this->assertTrue($listener->called);
    }

    public function testAddEventListenerWithMultipleEvents(): void
    {
        $listener = new class {
            public int $callCount = 0;

            public function prePersist(EventArgs $args): void
            {
                $this->callCount++;
            }

            public function postPersist(EventArgs $args): void
            {
                $this->callCount++;
            }
        };

        $this->eventManager->addEventListener([Events::PRE_PERSIST, Events::POST_PERSIST], $listener);
        $this->eventManager->dispatchEvent(Events::PRE_PERSIST);
        $this->eventManager->dispatchEvent(Events::POST_PERSIST);

        $this->assertSame(2, $listener->callCount);
    }

    public function testRemoveEventListener(): void
    {
        $listener = new class {
            public bool $called = false;

            public function prePersist(EventArgs $args): void
            {
                $this->called = true;
            }
        };

        $this->eventManager->addEventListener(Events::PRE_PERSIST, $listener);
        $this->eventManager->removeEventListener(Events::PRE_PERSIST, $listener);
        $this->eventManager->dispatchEvent(Events::PRE_PERSIST);

        $this->assertFalse($listener->called);
    }

    public function testAddEventSubscriber(): void
    {
        $subscriber = new class implements EventSubscriberInterface {
            public bool $called = false;

            public function getSubscribedEvents(): array
            {
                return [
                    Events::PRE_PERSIST => 'prePersist',
                ];
            }

            public function prePersist(EventArgs $args): void
            {
                $this->called = true;
            }
        };

        $this->eventManager->addEventSubscriber($subscriber);
        $this->eventManager->dispatchEvent(Events::PRE_PERSIST);

        $this->assertTrue($subscriber->called);
    }

    public function testRemoveEventSubscriber(): void
    {
        $subscriber = new class implements EventSubscriberInterface {
            public bool $called = false;

            public function getSubscribedEvents(): array
            {
                return [
                    Events::PRE_PERSIST => 'onPrePersist',
                ];
            }

            public function onPrePersist(EventArgs $args): void
            {
                $this->called = true;
            }
        };

        $this->eventManager->addEventSubscriber($subscriber);
        $this->eventManager->removeEventSubscriber($subscriber);
        $this->eventManager->dispatchEvent(Events::PRE_PERSIST);

        $this->assertFalse($subscriber->called);
    }

    public function testDispatchWithEventArgs(): void
    {
        $object = new \stdClass();
        $object->name = 'test';

        $listener = new class {
            public ?object $receivedObject = null;

            public function prePersist(EventArgs $args): void
            {
                $this->receivedObject = $args->getObject();
            }
        };

        $this->eventManager->addEventListener(Events::PRE_PERSIST, $listener);
        $this->eventManager->dispatchEvent(Events::PRE_PERSIST, new EventArgs($object));

        $this->assertSame($object, $listener->receivedObject);
        $this->assertSame('test', $listener->receivedObject->name);
    }

    public function testHasListeners(): void
    {
        $this->assertFalse($this->eventManager->hasListeners(Events::PRE_PERSIST));

        $listener = new class {
            public function prePersist(EventArgs $args): void {}
        };

        $this->eventManager->addEventListener(Events::PRE_PERSIST, $listener);
        $this->assertTrue($this->eventManager->hasListeners(Events::PRE_PERSIST));
    }

    public function testGetListeners(): void
    {
        $listener1 = new class {
            public function prePersist(EventArgs $args): void {}
        };
        $listener2 = new class {
            public function prePersist(EventArgs $args): void {}
        };

        $this->eventManager->addEventListener(Events::PRE_PERSIST, $listener1);
        $this->eventManager->addEventListener(Events::PRE_PERSIST, $listener2);

        $listeners = $this->eventManager->getListeners(Events::PRE_PERSIST);

        $this->assertCount(2, $listeners);
        $this->assertSame($listener1, $listeners[0]);
        $this->assertSame($listener2, $listeners[1]);
    }

    public function testDispatchWithCallableListener(): void
    {
        $called = false;
        $listener = function (EventArgs $args) use (&$called): void {
            $called = true;
        };

        $this->eventManager->addEventListener(Events::PRE_PERSIST, $listener);
        $this->eventManager->dispatchEvent(Events::PRE_PERSIST);

        $this->assertTrue($called);
    }

    public function testDispatchWithoutListenersDoesNothing(): void
    {
        $this->eventManager->dispatchEvent(Events::PRE_PERSIST);
        $this->assertTrue(true);
    }

    public function testMultipleListenersExecutedInOrder(): void
    {
        $order = [];

        $listener1 = new class($order) {
            public function __construct(private array &$order) {}

            public function prePersist(EventArgs $args): void
            {
                $this->order[] = 'listener1';
            }
        };

        $listener2 = new class($order) {
            public function __construct(private array &$order) {}

            public function prePersist(EventArgs $args): void
            {
                $this->order[] = 'listener2';
            }
        };

        $this->eventManager->addEventListener(Events::PRE_PERSIST, $listener1);
        $this->eventManager->addEventListener(Events::PRE_PERSIST, $listener2);
        $this->eventManager->dispatchEvent(Events::PRE_PERSIST);

        $this->assertSame(['listener1', 'listener2'], $order);
    }
}
