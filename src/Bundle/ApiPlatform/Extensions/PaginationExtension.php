<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Bundle\ApiPlatform\Extensions;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use Talleu\RedisOm\Bundle\ApiPlatform\RedisPaginator;
use Talleu\RedisOm\Om\RedisObjectManagerInterface;

class PaginationExtension implements QueryResultCollectionExtensionInterface
{
    public function __construct(private RedisObjectManagerInterface $redisObjectManager, private readonly ?Pagination $pagination)
    {
    }

    public function buildParams(array $params, string $resourceClass, ?Operation $operation = null, array $context = []): array
    {
        if (null === $pagination = $this->getPagination($params, $operation, $context)) {
            return $params;
        }

        [$params['offset'], $params['limit']] = $pagination;

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsResult(string $resourceClass, ?Operation $operation = null, array $context = []): bool
    {
        if ($context['graphql_operation_name'] ?? false) {
            return $this->pagination->isGraphQlEnabled($operation, $context);
        }

        return $this->pagination->isEnabled($operation, $context) && method_exists(self::class, 'getResult');
    }

    public function getResult(array $params, ?string $resourceClass = null): iterable
    {
        $repository = $this->redisObjectManager->getRepository($resourceClass);

        return new RedisPaginator($repository, $params);
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function getPagination(array $params, ?Operation $operation, array $context): ?array
    {
        $enabled = isset($context['graphql_operation_name']) ? $this->pagination->isGraphQlEnabled($operation, $context) : $this->pagination->isEnabled($operation, $context);

        if (!$enabled) {
            return null;
        }

        $context = $this->addCountToContext($params, $context);

        return \array_slice($this->pagination->getPagination($operation, $context), 1);
    }

    private function addCountToContext(array $params, array $context): array
    {
        if (!($context['graphql_operation_name'] ?? false)) {
            return $context;
        }

        if (isset($context['filters']['last']) && !isset($context['filters']['before'])) {
            $count = $this->redisObjectManager->getRepository($context['ressource_class'])->count($params['criteria'] ?? []);
            $context['count'] = $count;
        }

        return $context;
    }
}
