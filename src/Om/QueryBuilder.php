<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om;

use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Om\Converters\ConverterInterface;

class QueryBuilder
{
    private ?string $query = null;

    public function __construct(
        private RedisClientInterface $redisClient,
        private ConverterInterface $converter,
        private string $className,
        private string $redisKey,
        private string $format
    ) {
    }

    public function query(string $query): void
    {
        $this->query = $query;
    }

    public function execute(): array
    {
        $data = $this->redisClient->customSearch($this->redisKey, $this->query, $this->format);

        $collection = [];
        foreach ($data as $item) {
            $collection[] = $this->converter->revert($item, $this->className);
        }

        return $collection;
    }
}
