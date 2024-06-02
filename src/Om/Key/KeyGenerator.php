<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Key;

use Talleu\RedisOm\Exception\BadIdentifierConfigurationException;
use Talleu\RedisOm\Om\Mapping\Entity;
use Talleu\RedisOm\Om\Mapping\Id;

class KeyGenerator
{
    public const DEFAULT_IDENTIFIER = 'id';

    public function generateKey(Entity $redisEntity, object &$object): string
    {
        $prefix = $redisEntity->prefix ?? get_class($object);
        $identifierProperty = $this->getIdentifier(new \ReflectionClass($object));

        $identifierValue = $object->{$identifierProperty->getName()};
        if (!$identifierValue) {
            $identifierValue = uniqid();
            if ($identifierProperty->getType()?->getName() === 'int') {
                $identifierValue = (int) hexdec($identifierValue);
            }
            $object->{$identifierProperty->getName()} = $identifierValue;
        } elseif (!is_int($identifierValue) && !is_string($identifierValue)) {
            throw new BadIdentifierConfigurationException(sprintf('The identifier value you provide : %s must be a type string or int, %s given', $identifierProperty, gettype($identifierValue)));
        }

        return sprintf('%s:%s', $prefix, $identifierValue);
    }

    public function getIdentifier(\ReflectionClass $reflectionClass): \ReflectionProperty
    {
        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->getAttributes(Id::class) !== []) {
                $identifierName = $property->getName();
                break;
            }
        }

        $identifierName = $identifierName ?? self::DEFAULT_IDENTIFIER;
        try {
            $reflectionProperty = $reflectionClass->getProperty($identifierName);
        } catch (\ReflectionException $e) {
            throw new BadIdentifierConfigurationException('You must specify a property as identifier, or have an $id property that will be identifier by default.');
        }

        if (!$reflectionProperty->isPublic()) {
            throw new BadIdentifierConfigurationException(sprintf('The identifier #%s is not accessible', $identifierName));
        }

        return $reflectionProperty;
    }
}
