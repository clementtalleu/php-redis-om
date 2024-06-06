<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters;

use Talleu\RedisOm\Exception\BadPropertyConfigurationException;
use Talleu\RedisOm\Om\Mapping\Property;

abstract class AbstractObjectConverter implements ConverterInterface
{
    abstract public function convert($data): array;

    public function supportsConversion(string $type, mixed $data): bool
    {
        return $data !== null && class_exists($type) && $type !== \stdClass::class && !in_array($type, AbstractDateTimeConverter::DATETYPES_NAMES);
    }

    abstract public function revert($data, string $type): mixed;

    public function supportsReversion(string $type, mixed $value): bool
    {
        return class_exists($type) && !in_array($type, AbstractDateTimeConverter::DATETYPES_NAMES);
    }

    protected function extractPropertyValue(Property $propertyAttribute, \ReflectionProperty $property, object $data): mixed
    {
        if ($property->isPublic()) {
            $value = $data->{$property->getName()};
        } elseif ($propertyAttribute->getter) {
            if (!method_exists($data, $propertyAttribute->getter)) {
                throw new BadPropertyConfigurationException(sprintf("The getter you provide %s() does not exist in class %s", $propertyAttribute->getter, get_class($data)));
            }
            $value = $data->{$propertyAttribute->getter}();
        } elseif (method_exists($data, sprintf("get%s", ucfirst($property->getName())))) {
            $value = $data->{sprintf("get%s", ucfirst($property->getName()))}();
        } else {
            throw new BadPropertyConfigurationException(sprintf("The property %s is not accessible, you should change the visibility to public or implements a get%s() method", $property->getName(), ucfirst($property->getName())));
        }

        return $value;
    }

    protected function assignValue(object &$object, string $key, mixed $revertedValue, string $type, ?\ReflectionProperty $reflectionProperty = null): void
    {
        $reflectionProperty = $reflectionProperty ?? new \ReflectionProperty($type, $key);
        if ($reflectionProperty->isPublic()) {
            $object->{$key} = $revertedValue;
            return;
        }

        /** @var Property|null $propertyAttribute */
        $propertyAttribute = $reflectionProperty->getAttributes(Property::class) !== [] ? $reflectionProperty->getAttributes(Property::class)[0]->newInstance() : null;

        if ($propertyAttribute && $propertyAttribute->setter !== null) {
            if (!method_exists($object, $propertyAttribute->setter)) {
                throw new BadPropertyConfigurationException(sprintf("The setter you provide %s() does not exist in class %s", $propertyAttribute->setter, get_class($object)));
            }
            $object->{$propertyAttribute->setter}($revertedValue);
        } elseif (method_exists($object, sprintf("set%s", ucfirst($key)))) {
            $object->{sprintf("set%s", ucfirst($key))}($revertedValue);
        } else {
            throw new BadPropertyConfigurationException(sprintf("The property %s is not accessible, you should change the visibility to public or implements a set%s() method", $key, ucfirst($key)));
        }
    }
}
