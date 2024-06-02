<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Repository\HashModel;

use Talleu\RedisOm\Om\RedisFormat;
use Talleu\RedisOm\Om\Repository\AbstractObjectRepository;

final class HashRepository extends AbstractObjectRepository
{
    public ?string $format = RedisFormat::HASH->value;

    public function find(string $identifier): ?object
    {
        $data = $this->redisClient->hashGetAll("$this->prefix:$identifier");
        if (!$data) {
            return null;
        }

        return $this->converter->revert($data, $this->className);
    }

    // @todo
    // public function getPropertyValue(string $identifier, string $property): mixed
    // {
    //     return $this->redisClient->hget("$this->prefix:$identifier", $property);
    // }
}
