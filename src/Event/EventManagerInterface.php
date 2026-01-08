<?php declare(strict_types=1);

namespace Talleu\RedisOm\Event;

interface EventManagerInterface
{
    /**
     * Dispatches an event to registered listeners.
     */
    public function dispatchEvent(string $eventName, ?EventArgs $eventArgs = null): void;

    /**
     * Adds a listener for one or more events.
     *
     * @param string|string[] $events
     */
    public function addEventListener(string|array $events, object $listener): void;

    /**
     * Removes a listener for one or more events.
     *
     * @param string|string[] $events
     */
    public function removeEventListener(string|array $events, object $listener): void;

    /**
     * Adds an event subscriber.
     */
    public function addEventSubscriber(EventSubscriberInterface $subscriber): void;

    /**
     * Retire un event subscriber.
     */
    public function removeEventSubscriber(EventSubscriberInterface $subscriber): void;

    /**
     * Returns all listeners for a given event.
     *
     * @return object[]
     */
    public function getListeners(string $event): array;

    /**
     * Checks if any listeners are registered for an event.
     */
    public function hasListeners(string $event): bool;
}
