<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\ApiPlatform;

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Talleu\RedisOm\Bundle\TalleuRedisOmBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new ApiPlatformBundle(),
            new TalleuRedisOmBundle(),
        ];
    }

    public function configureRoutes(RoutingConfigurator $routes)
    {
        $routes->import('.', 'api_platform')
            ->prefix('/api');
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'ChangeMe',
            'test' => true,
        ]);

        $apiPlatformConfig = [
            'mapping' => [
                'paths' => [
                    __DIR__.'/Entity',
                ],
            ],
        ];

        $container
            ->extension('api_platform', $apiPlatformConfig);
    }

    #[Route('/test', name: 'test')]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse();
    }

    public function getCacheDir(): string
    {
        return 'var/cache';
    }

    public function getLogDir(): string
    {
        return 'var/logs';
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }
}
