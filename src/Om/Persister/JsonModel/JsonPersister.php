<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Persister\JsonModel;

use Talleu\RedisOm\Om\Persister\AbstractPersister;

final class JsonPersister extends AbstractPersister
{
    /**
     * @inheritdoc
     */
    public function doPersist(array $objectsToPersist): void
    {
        if ($objectsToPersist === []) {
            return;
        }

        if (count($objectsToPersist) === 1) {
            $objectToPersist = reset($objectsToPersist);
            $this->redis->jsonSet(key: $objectToPersist->redisKey, value: \json_encode($objectToPersist->converter->convert($objectToPersist->value)));
            return;
        }

        $redisData = [];
        foreach ($objectsToPersist as $objectToPersist) {
            $redisData[] = $objectToPersist->redisKey;
            $redisData[] = null;
            $redisData[] = \json_encode($objectToPersist->converter->convert($objectToPersist->value));
        }

        $this->redis->jsonMSet($redisData);

        foreach ($objectsToPersist as $objectToPersist) {
            if (null !== $objectToPersist->ttl) {
                $this->redis->expire($objectToPersist->redisKey, $objectToPersist->ttl);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function doDelete(array $objectsToRemove): void
    {
        foreach ($objectsToRemove as $objectToRemove) {
            $this->redis->jsonDel($objectToRemove->redisKey);
        }
    }
}
