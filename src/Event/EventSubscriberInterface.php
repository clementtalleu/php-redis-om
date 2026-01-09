<?php declare(strict_types=1);

namespace Talleu\RedisOm\Event;

interface EventSubscriberInterface
{
    /**
     * Returns the subscribed events.
     *
     * @return array<string, string|array> Format : ['eventName' => 'methodName'] or ['eventName' => ['methodName', priority]]
     */
    public function getSubscribedEvents(): array;
}
