<?php

declare(strict_types=1);

namespace  Talleu\RedisOm\Om\Persister;

final class ObjectToPersist
{
    public function __construct(
        public string $persisterClass,
        public string $operation,
        public string $redisKey,
        public string|array|null $value = null
    ) {
    }
}
