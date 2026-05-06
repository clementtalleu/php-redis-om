<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Console;

use Talleu\RedisOm\Client\RedisClientInterface;
use Talleu\RedisOm\Command\GenerateSchema;

final class Runner
{
    public static function generateSchema(string $dirPath, ?RedisClientInterface $redisClient = null): void
    {
        if (!is_dir($dirPath)) {
            $dirPath = self::projectRoot() . '/' . $dirPath;
            if (!is_dir($dirPath)) {
                throw new \InvalidArgumentException(sprintf('Directory %s not found', $dirPath));
            }
        }

        GenerateSchema::generateSchema($dirPath, $redisClient);
    }

    private static function projectRoot(): string
    {
        $dir = __DIR__;
        while ($dir !== dirname($dir)) {
            if (file_exists($dir . '/vendor/autoload.php')) {
                return $dir;
            }
            $dir = dirname($dir);
        }

        throw new \RuntimeException('Cannot locate project root');
    }
}
