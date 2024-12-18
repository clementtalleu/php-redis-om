<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('talleu_php_redis_om');

        $treeBuilder->getRootNode()
            ->children()
            ->arrayNode('redis_client')
            ->defaultValue('')
            ->useAttributeAsKey('type')
            ->scalarPrototype()
            ->validate()
            ->end()
            ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}