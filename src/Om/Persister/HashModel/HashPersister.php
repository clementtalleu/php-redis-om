<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Persister\HashModel;

use Talleu\RedisOm\Om\Persister\AbstractPersister;

final class HashPersister extends AbstractPersister
{
    /**
     * @inheritdoc
     */
    public function doPersist(array $objectsToPersist): void
    {
        if ($objectsToPersist === []) {
            return;
        }

        foreach ($objectsToPersist as $objectToPersist) {
            $this->redis->hMSet($objectToPersist->redisKey, $objectToPersist->converter->convert($objectToPersist->value));
            if (null !== $objectToPersist->ttl) {
                $this->redis->expire($objectToPersist->redisKey, $objectToPersist->ttl);
            }
        }
    }

    public function doDelete(array $objectsToRemove): void
    {
        foreach ($objectsToRemove as $objectToRemove) {
            $this->redis->del($objectToRemove->redisKey);
        }
    }
}
