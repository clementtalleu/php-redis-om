<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters;

use Symfony\Component\PropertyAccess\PropertyAccess;
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

    abstract public function supportsReversion(string $type, mixed $value): bool;

    protected function extractPropertyValue(Property $propertyAttribute, \ReflectionProperty $property, object $data): mixed
    {
        if ($property->isPublic()) {
            $value = $data->{$property->getName()};
        } elseif ($propertyAttribute->getter) {
            if (!method_exists($data, $propertyAttribute->getter)) {
                throw new BadPropertyConfigurationException(sprintf('The getter you provide %s() does not exist in class %s', $propertyAttribute->getter, get_class($data)));
            }
            $value = $data->{$propertyAttribute->getter}();
        } elseif (method_exists($data, sprintf('get%s', ucfirst($property->getName())))) {
            $value = $data->{sprintf('get%s', ucfirst($property->getName()))}();
        } else {
            throw new BadPropertyConfigurationException(sprintf('The property %s is not accessible, you should change the visibility to public or implements a get%s() method', $property->getName(), ucfirst($property->getName())));
        }

        return $value;
    }

    protected function assignValue(string $className, array $data)
    {
        static $reflectionCache = [];
        static $propertyAccessor;

        if (!$propertyAccessor) {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        if (!isset($reflectionCache[$className])) {
            $reflectionCache[$className] = new \ReflectionClass($className);
        }

        $reflectionClass = $reflectionCache[$className];
        $constructor = $reflectionClass->getConstructor();

        if ($constructor) {
            $constructorArgs = $this->mapConstructorArgs($constructor, $data);
            $object = $reflectionClass->newInstanceArgs($constructorArgs);
        } else {
            $object = $reflectionClass->newInstanceWithoutConstructor();
        }

        foreach ($data as $key => $value) {
            $reflectionProperty = $reflectionClass->hasProperty($key) ? $reflectionClass->getProperty($key) : null;

            if ($reflectionProperty && !$reflectionProperty->isReadOnly()) {
                $this->hydrateProperty($object, $reflectionProperty, $key, $value, $propertyAccessor);
            }
        }

        return $object;
    }

    private function mapConstructorArgs(\ReflectionMethod $constructor, array $data): array
    {
        $constructorArgs = [];

        foreach ($constructor->getParameters() as $param) {
            $paramName = $param->getName();
            if (array_key_exists($paramName, $data)) {
                $constructorArgs[] = $data[$paramName];
            } elseif ($param->isDefaultValueAvailable()) {
                $constructorArgs[] = $param->getDefaultValue();
            } else {
                throw new \InvalidArgumentException("Missing value for constructor parameter: $paramName");
            }
        }

        return $constructorArgs;
    }

    private function hydrateProperty(object $object, \ReflectionProperty $reflectionProperty, string $key, $value, $propertyAccessor): void
    {
        /** @var Property|null $propertyAttribute */
        $propertyAttribute = $reflectionProperty->getAttributes(Property::class) !== []
            ? $reflectionProperty->getAttributes(Property::class)[0]->newInstance()
            : null;

        if ($propertyAttribute && $propertyAttribute->setter !== null) {
            if (!method_exists($object, $propertyAttribute->setter)) {
                throw new BadPropertyConfigurationException(sprintf(
                    'The setter you provide %s() does not exist in class %s',
                    $propertyAttribute->setter,
                    get_class($object)
                ));
            }
            $object->{$propertyAttribute->setter}($value);
        } elseif (method_exists($object, sprintf('set%s', ucfirst($key)))) {
            $object->{sprintf('set%s', ucfirst($key))}($value);
        } else {
            if (!$propertyAccessor->isWritable($object, $key)) {
                throw new BadPropertyConfigurationException(sprintf(
                    'The property %s is not accessible, you should change the visibility to public or implement a set%s() method',
                    $key,
                    ucfirst($key)
                ));
            }
            $propertyAccessor->setValue($object, $key, $value);
        }
    }

}
