<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Persister\JsonModel;

use Talleu\RedisOm\Om\Persister\AbstractPersister;

final class JsonPersister extends AbstractPersister
{
    public function doPersist(string $key, array|\stdClass $data): void
    {
        $this->redis->jsonSet(key: $key, value: \json_encode($data));
    }

    public function doDelete(string $key): void
    {
        $this->redis->jsonDel($key);
    }
}
