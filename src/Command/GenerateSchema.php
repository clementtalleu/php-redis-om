<?php

declare(strict_types=1);

namespace  Talleu\RedisOm\Command;

use Talleu\RedisOm\Om\Mapping\Entity;
use Talleu\RedisOm\Om\RedisFormat;

class GenerateSchema
{
    public static function generateSchema(string $dir): void
    {
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        $phpFiles = [];

        foreach ($rii as $file) {
            if ($file->isDir()) {
                continue;
            }
            if ($file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }

        foreach ($phpFiles as $phpFile) {

            $namespace = static::getNamespace($phpFile);
            $class = static::getClass($phpFile);
            $fqcn = $namespace.'\\'.$class;

            try {
                $reflectionClass = new \ReflectionClass($fqcn);
            } catch (\ReflectionException $e) {
                continue;
            }

            $attributes = $reflectionClass->getAttributes(Entity::class);
            if ($attributes === []) {
                continue;
            }

            $properties = $reflectionClass->getProperties();

            /** @var Entity $entity */
            $entity = $attributes[0]->newInstance();
            $entity->redisClient->dropIndex($entity->prefix ?? $fqcn);
            $entity->redisClient->createIndex($entity->prefix ?? $fqcn, $entity->format ?? RedisFormat::HASH->value, $properties);
        }
    }

    protected static function getNamespace(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $src = file_get_contents($filePath);
        if (preg_match('#^namespace\s+(.+?);$#sm', $src, $m)) {
            return $m[1];
        }

        return null;
    }

    protected static function getClass(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $src = file_get_contents($filePath);
        if (preg_match('/\bclass\s+(\w+)\s*[^{]/', $src, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
