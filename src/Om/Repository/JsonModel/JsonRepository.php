<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Repository\JsonModel;

use Talleu\RedisOm\Om\RedisFormat;
use Talleu\RedisOm\Om\Repository\AbstractObjectRepository;

final class JsonRepository extends AbstractObjectRepository
{
    public ?string $format = RedisFormat::JSON->value;

    public function find(string $identifier): ?object
    {
        $data = $this->redisClient->jsonget("$this->prefix:$identifier");
        if (!$data) {
            return null;
        }

        return $this->converter->revert(\json_decode($data, true), $this->className);
    }
}
