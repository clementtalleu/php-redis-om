<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Persister\HashModel;

use Talleu\RedisOm\Om\Persister\AbstractPersister;

final class HashPersister extends AbstractPersister
{
    public function doPersist(string $key, $data): void
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Redis hMSet() method #2 argument must be an array.');
        }

        $this->redis->hMSet($key, $data);
    }

    public function doDelete(string $key): void
    {
        $this->redis->del($key);
    }
}
