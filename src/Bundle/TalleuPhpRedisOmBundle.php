<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Bundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Talleu\RedisOm\Bundle\DependencyInjection\TalleuPhpRedisOmExtension;

final class TalleuPhpRedisOmBundle extends Bundle
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
            $this->extension = new TalleuPhpRedisOmExtension();
        }

        return $this->extension;
    }
}
