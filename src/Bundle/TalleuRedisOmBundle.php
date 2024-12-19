<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Bundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Talleu\RedisOm\Bundle\DependencyInjection\TalleuRedisOmExtension;

final class TalleuRedisOmBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new TalleuRedisOmExtension();
        }

        return $this->extension;
    }
}
