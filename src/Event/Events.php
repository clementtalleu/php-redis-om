<?php declare(strict_types=1);

namespace Talleu\RedisOm\Event;

final class Events
{
    public const PRE_PERSIST = 'prePersist';
    public const POST_PERSIST = 'postPersist';

    public const PRE_REMOVE = 'preRemove';
    public const POST_REMOVE = 'postRemove';

    public const POST_FLUSH = 'postFlush';

    private function __construct() {
        // Must be private, we must not instance this class
    }
}
