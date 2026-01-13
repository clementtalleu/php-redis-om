<?php declare(strict_types=1);

namespace Talleu\RedisOm\Event;

use Talleu\RedisOm\Om\RedisObjectManagerInterface;

final class LifecycleEventArgs extends EventArgs
{
    public function __construct(
        ?object $object,
        private readonly RedisObjectManagerInterface $objectManager
    ) {
        parent::__construct($object);
    }

    public function getObjectManager(): RedisObjectManagerInterface
    {
        return $this->objectManager;
    }
}
