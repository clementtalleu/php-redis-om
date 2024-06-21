<?php

declare(strict_types=1);

namespace  Talleu\RedisOm\Command;

use Talleu\RedisOm\Exception\BadIdentifierConfigurationException;
use Talleu\RedisOm\Om\Mapping\Entity;
use Talleu\RedisOm\Om\Mapping\Id;
use Talleu\RedisOm\Om\Mapping\Property;
use Talleu\RedisOm\Om\RedisFormat;

final class GenerateSchema
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

            $namespace = self::getNamespace($phpFile);
            $class = self::getClass($phpFile);
            $fqcn = "$namespace\\$class";

            try {
                $reflectionClass = new \ReflectionClass($fqcn);
            } catch (\ReflectionException) {
                continue;
            }

            $attributes = $reflectionClass->getAttributes(Entity::class);
            if ($attributes === []) {
                continue;
            }

            /** @var Entity $entity */
            $entity = $attributes[0]->newInstance();
            $entity->redisClient->dropIndex($entity->prefix ?? $fqcn);
            $format = $entity->format ?? RedisFormat::HASH->value;

            $idExist = false;
            $propertiesToIndex = [];
            $properties = $reflectionClass->getProperties();
            foreach ($properties as $reflectionProperty) {
                if (($propertyAttribute = $reflectionProperty->getAttributes(Property::class)) === []) {
                    continue;
                }

                if ($reflectionProperty->getAttributes(Id::class) !== []) {
                    $idExist = true;
                }

                /** @var Property $property */
                $property = $propertyAttribute[0]->newInstance();
                /** @var \ReflectionNamedType|null $propertyReflectionType */
                $propertyReflectionType = $reflectionProperty->getType();
                $propertyType = $propertyReflectionType->getName();
                $propertyName = $property->name ?? $reflectionProperty->getName();

                if (!in_array($propertyType, ['int', 'string', 'float', 'bool']) && !class_exists($propertyType)) {
                    continue;
                }

                if (class_exists($propertyType)) {
                    $subReflectionClass = new \ReflectionClass($propertyType);
                    $attributes = $subReflectionClass->getAttributes(Entity::class);
                    if ($attributes === []) {
                        continue;
                    }

                    $subProperties = $subReflectionClass->getProperties();
                    foreach ($subProperties as $subReflectionProperty) {
                        if (($subPropertyAttribute = $subReflectionProperty->getAttributes(Property::class)) === []) {
                            continue;
                        }

                        /** @var Property $subProperty */
                        $subProperty = $subPropertyAttribute[0]->newInstance();
                        /** @var \ReflectionNamedType|null $subPropertyType */
                        $subPropertyType = $subReflectionProperty->getType();

                        $subPropertyName = $subProperty->name ?? $subReflectionProperty->getName();
                        if (!in_array($subPropertyType?->getName(), ['int', 'string', 'float', 'bool'])) {
                            continue;
                        }

                        $propertiesToIndex["$propertyName.$subPropertyName"] = $propertyName.'_'.$subPropertyName;
                    }
                } else {
                    $propertiesToIndex[($property->name !== null ? $property->name : $reflectionProperty->name)] = $reflectionProperty->name;
                }
            }

            if (!$idExist) {
                throw new BadIdentifierConfigurationException("No identifier found for $fqcn, or identifier is not mapped by RedisOm");
            }

            $entity->redisClient->createIndex($entity->prefix ?? $fqcn, $format, $propertiesToIndex);
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
