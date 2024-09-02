<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Persister;

use Talleu\RedisOm\Om\Converters\ConverterInterface;

final class ObjectToPersist
{
    public function __construct(
        public string $persisterClass,
        public string $operation,
        public string $redisKey,
        public ?ConverterInterface $converter = null,
        public object|array|null $value = null
    ) {
    }
}
