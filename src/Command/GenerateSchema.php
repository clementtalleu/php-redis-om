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
            $fqcn = static::getFQCNFromFile($phpFile);
            if (!$fqcn) {
                continue;
            }

            try {
                $reflectionClass = new \ReflectionClass($fqcn);
            } catch (\Exception $e) {
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

    private static function getFQCNFromFile(string $filePath): ?string
    {
        $tokens = token_get_all(file_get_contents($filePath));
        $count = count($tokens);

        $nextTokenNs = false;
        $namespace = '';
        for ($i = 0; $i < $count; $i++) {

            if (!is_array($tokens[$i])) {
                continue;
            }

            if ($tokens[$i][1] === 'namespace') {
                $nextTokenNs = true;
                continue;
            }

            if ($nextTokenNs && !ctype_space($tokens[$i][1])) {
                $namespace = $tokens[$i][1];
                break;
            }
        }

        $nextTokenClass = false;
        $className = null;
        for ($i = 0; $i < $count; $i++) {

            if (!is_array($tokens[$i])) {
                continue;
            }

            if ($tokens[$i][1] === 'class') {
                $nextTokenClass = true;
                continue;
            }

            if ($nextTokenClass && !ctype_space($tokens[$i][1])) {
                $className = $tokens[$i][1];
                break;
            }
        }

        if (!$className) {
            return null;
        }

        return $namespace.'\\'.$className;
    }
}
