<?php declare(strict_types=1);

namespace Talleu\RedisOm\Event;

class EventArgs
{
    public function __construct(
        private readonly ?object $object = null,
    ) {}

    public function getObject(): ?object
    {
        return $this->object;
    }
}
