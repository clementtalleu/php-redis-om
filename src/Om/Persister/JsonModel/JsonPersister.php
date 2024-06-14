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
            $this->redis->jsonSet(key: $objectsToPersist[1]->redisKey, value: \json_encode($objectsToPersist[1]->value));
            return;
        }

        $redisData = [];
        foreach ($objectsToPersist as $objectToPersist) {
            $redisData = [$objectToPersist->redisKey, null, \json_encode($objectToPersist->value)];
        }

        $this->redis->jsonMSet($redisData);
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
