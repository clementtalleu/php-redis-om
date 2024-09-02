<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Command;

use Talleu\RedisOm\Exception\BadIdentifierConfigurationException;
use Talleu\RedisOm\Om\Converters\AbstractDateTimeConverter;
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
                    if ($idExist) {
                        throw new BadIdentifierConfigurationException("Multiple identifiers found for $fqcn, only one is allowed");
                    }

                    $idExist = true;
                }

                /** @var Property $property */
                $property = $propertyAttribute[0]->newInstance();

                /** This property is not indexed neither an ID */
                if (is_null($property->index) && $reflectionProperty->getAttributes(Id::class) === []) {
                    continue;
                }

                /** @var \ReflectionNamedType|null $propertyReflectionType */
                $propertyReflectionType = $reflectionProperty->getType();
                $propertyType = $propertyReflectionType->getName();
                $propertyName = $reflectionProperty->getName();

                // Index disabled
                if (empty($property->index)) {
                    continue;
                }

                // Custom index provided
                if (is_array($property->index)) {
                    foreach ($property->index as $indexName => $indexType) {
                        $propertiesToIndex[] = new PropertyToIndex($propertyName, $indexName, $indexType);
                    }

                    continue;
                }

                // Property type not supported for indexing
                if (!in_array($propertyType, ['int', 'string', 'float', 'bool']) && !class_exists($propertyType)) {
                    continue;
                }

                if (in_array($propertyType, AbstractDateTimeConverter::DATETYPES_NAMES)) {
                    if ($format === RedisFormat::HASH->value) {
                        $propertiesToIndex[] = new PropertyToIndex("$propertyName#timestamp", $propertyName, Property::INDEX_TAG);
                        $propertiesToIndex[] = new PropertyToIndex("$propertyName#timestamp", $propertyName.'_text', Property::INDEX_TEXT);
                    } else {
                        $propertiesToIndex[] = new PropertyToIndex('$.'. "$propertyName.timestamp", $propertyName, Property::INDEX_TAG);
                        $propertiesToIndex[] = new PropertyToIndex('$.'. "$propertyName.timestamp", $propertyName."_text", Property::INDEX_TEXT);
                    }
                } elseif ($propertyType === 'int' || $propertyType === 'float') {
                    if ($format === RedisFormat::HASH->value) {
                        $propertiesToIndex[] = new PropertyToIndex($propertyName, $propertyName, Property::INDEX_TAG);
                    } else {
                        $propertiesToIndex[] = new PropertyToIndex('$.'.$propertyName, $propertyName, Property::INDEX_TAG);
                    }
                } elseif (class_exists($propertyType)) {
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
                        if (empty($subProperty->index) && $subReflectionProperty->getAttributes(Id::class) === []) {
                            continue;
                        }

                        /** @var \ReflectionNamedType|null $subPropertyType */
                        $subPropertyType = $subReflectionProperty->getType();
                        $subPropertyName = $subReflectionProperty->getName();
                        if (!in_array($subPropertyType?->getName(), ['int', 'string', 'float', 'bool'])) {
                            continue;
                        }

                        $propertiesToIndex[] = new PropertyToIndex(($format === RedisFormat::JSON->value ? '$.' : '')."$propertyName.$subPropertyName", $propertyName.'_'.$subPropertyName.'_text', Property::INDEX_TEXT);
                        $propertiesToIndex[] = new PropertyToIndex(($format === RedisFormat::JSON->value ? '$.' : ''). "$propertyName.$subPropertyName", $propertyName.'_'.$subPropertyName, Property::INDEX_TAG);
                    }
                } else {
                    $propertiesToIndex[] = new PropertyToIndex(($format === RedisFormat::JSON->value ? '$.' : '').$propertyName, $propertyName, Property::INDEX_TAG);
                    $propertiesToIndex[] = new PropertyToIndex(($format === RedisFormat::JSON->value ? '$.' : '').$propertyName, $propertyName.'_text', Property::INDEX_TEXT);
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

class PropertyToIndex
{
    public function __construct(public string $name, public string $indexName, public string $indexType)
    {
    }
}
