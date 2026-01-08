<?php declare(strict_types=1);

namespace Talleu\RedisOm\Event;

final class EventManager implements EventManagerInterface
{
    /**
     * @var array<string, object[]>
     */
    private array $listeners = [];

    public function dispatchEvent(string $eventName, ?EventArgs $eventArgs = null): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        $eventArgs ??= new EventArgs();

        foreach ($this->listeners[$eventName] as $listener) {
            if (method_exists($listener, $eventName)) {
                $listener->{$eventName}($eventArgs);
            } elseif (is_callable($listener)) {
                $listener($eventArgs);
            } elseif (method_exists($listener, '__invoke')) {
                $listener->__invoke($eventArgs);
            } elseif (is_array($listener) && count($listener) === 2) {
                [$object, $method] = $listener;
                $object->{$method}($eventArgs);
            }
        }
    }

    public function addEventListener(string|array $events, object $listener): void
    {
        $events = is_array($events) ? $events : [$events];

        foreach ($events as $event) {
            $this->listeners[$event][] = $listener;
        }
    }

    public function removeEventListener(string|array $events, object $listener): void
    {
        $events = is_array($events) ? $events : [$events];

        foreach ($events as $event) {
            if (!isset($this->listeners[$event])) {
                continue;
            }

            $this->listeners[$event] = array_filter(
                $this->listeners[$event],
                static fn($registeredListener) => $registeredListener !== $listener
            );
        }
    }

    public function addEventSubscriber(EventSubscriberInterface $subscriber): void
    {
        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            $this->addEventListener($eventName, $subscriber);
        }
    }

    public function removeEventSubscriber(EventSubscriberInterface $subscriber): void
    {
        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            $this->removeEventListener($eventName, $subscriber);
        }
    }

    public function getListeners(string $event): array
    {
        return $this->listeners[$event] ?? [];
    }

    public function hasListeners(string $event): bool
    {
        return !empty($this->listeners[$event]);
    }
}
