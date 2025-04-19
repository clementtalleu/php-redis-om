<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Bundle\DependencyInjection;

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Talleu\RedisOm\ApiPlatform\Extensions\QueryCollectionExtensionInterface;
use Talleu\RedisOm\ApiPlatform\Filters\SearchFilter;
use Talleu\RedisOm\ApiPlatform\State\CollectionProvider;
use Talleu\RedisOm\ApiPlatform\State\ItemProvider;
use Talleu\RedisOm\ApiPlatform\State\RedisProcessor;
use Talleu\RedisOm\ApiPlatform\State\RedisProvider;
use Talleu\RedisOm\ApiPlatform\Filters\RedisSearchFilter;

final class TalleuRedisOmExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        // Check api-platform install
        if (class_exists(ApiPlatformBundle::class)) {
            $this->registerApiPlatformServices($container);
            $loader->load('api_platform.xml');
        }
    }

    private function registerApiPlatformServices(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(QueryCollectionExtensionInterface::class)
            ->addTag('talleu_php_redis_om.api_platform.query_extension.collection');

        $this->registerProviders($container);
        $this->registerProcessor($container);
        $this->registerFilters($container);
    }

    private function registerProviders(ContainerBuilder $container): void
    {
        $providers = [CollectionProvider::class, ItemProvider::class, RedisProvider::class];
        foreach ($providers as $provider) {
            $definition = new Definition($provider);
            $definition->addTag('api_platform.state_provider');
            $definition->setAutowired(true);
            $definition->setAutoconfigured(true);
            $container->setDefinition($provider, $definition);
        }
    }

    private function registerProcessor(ContainerBuilder $container): void
    {
        $providers = [RedisProcessor::class];
        foreach ($providers as $provider) {
            $definition = new Definition($provider);
            $definition->addTag('api_platform.state_processor');
            $definition->setAutowired(true);
            $definition->setAutoconfigured(true);
            $container->setDefinition($provider, $definition);
        }
    }

    private function registerFilters(ContainerBuilder $container): void
    {
        $definition = new Definition(SearchFilter::class);
        $definition->addTag('api_platform.filter');
        $definition->setAutowired(true);
        $definition->setAutoconfigured(true);

        $container->setDefinition(SearchFilter::class, $definition);
    }
}
