<?php declare(strict_types=1);

namespace Talleu\RedisOm\Event\Bridge;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Talleu\RedisOm\Event\EventArgs;
use Talleu\RedisOm\Event\EventManagerInterface;
use Talleu\RedisOm\Event\EventSubscriberInterface;

/**
 * Adapter that allows using Symfony’s EventDispatcher
 * while maintaining a framework-agnostic interface.
 */
final class SymfonyEventManagerAdapter implements EventManagerInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher
    ) {}

    public function dispatchEvent(string $eventName, ?EventArgs $eventArgs = null): void
    {
        $this->dispatcher->dispatch($eventArgs ?? new EventArgs(), $eventName);
    }

    public function addEventListener(string|array $events, object $listener): void
    {
        throw new \LogicException(
            'Use Symfony service tags to register event listeners when using SymfonyEventManagerAdapter'
        );
    }

    public function removeEventListener(string|array $events, object $listener): void
    {
        throw new \LogicException(
            'Listener removal not supported when using SymfonyEventManagerAdapter'
        );
    }

    public function addEventSubscriber(EventSubscriberInterface $subscriber): void
    {
        throw new \LogicException(
            'Use Symfony service tags to register event subscribers when using SymfonyEventManagerAdapter'
        );
    }

    public function removeEventSubscriber(EventSubscriberInterface $subscriber): void
    {
        throw new \LogicException(
            'Subscriber removal not supported when using SymfonyEventManagerAdapter'
        );
    }

    public function getListeners(string $event): array
    {
        return $this->dispatcher->getListeners($event);
    }

    public function hasListeners(string $event): bool
    {
        return $this->dispatcher->hasListeners($event);
    }
}
