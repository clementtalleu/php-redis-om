<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters\HashModel;

use Talleu\RedisOm\Om\Converters\AbstractObjectConverter;
use Talleu\RedisOm\Om\Converters\AbstractDateTimeConverter;
use Talleu\RedisOm\Om\Mapping\Property;

class HashObjectConverter extends AbstractObjectConverter
{
    public function convert($data, ?array $hashData = [], ?string $parentProperty = null): array
    {
        $reflection = new \ReflectionClass($data);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {

            $propertyAttribute = $property->getAttributes(Property::class) !== [] ? $property->getAttributes(Property::class)[0]->newInstance() : null;
            if (!$propertyAttribute) {
                continue;
            }

            $value = $data->{$property->getName()};
            $valueType = $property->getType() ? $property->getType()->getName() : (is_object($value) ? get_class($value) : gettype($value));
            $converter = ConverterFactory::getConverter($valueType, $value);
            if (!$converter) {
                continue;
            }

            if ($converter instanceof HashObjectConverter || $converter instanceof ArrayConverter) {
                $propertyName = $parentProperty ? sprintf('%s.%s', $parentProperty, $property->getName()) : $property->getName();
                $hashData = $converter->convert(data: $value, hashData:  $hashData, parentProperty: $propertyName);
                continue;
            }

            $convertedValue = $converter->convert($value);

            if ($parentProperty) {
                $hashData[sprintf('%s.%s', $parentProperty, $property->getName())] = $convertedValue;
                continue;
            }

            $hashData[$property->getName()] = $convertedValue;
        }

        return $hashData;
    }

    /**
     * @param array $data
     */
    public function revert($data, string $type): mixed
    {
        $data = $this->redisArrayUnflatten($data);
        $object = new $type();

        foreach ($data as $key => $value) {

            if (!property_exists($object, $key)) {
                continue;
            }

            if (is_array($value) && array_key_exists('#type', $value)) {
                $valueType = $value['#type'];
            } elseif (($reflectionProperty = new \ReflectionProperty($type, $key)) && $reflectionProperty->getType()) {
                $valueType = $reflectionProperty->getType()->getName();
            } else {
                $valueType = is_object($value) ? get_class($value) : gettype($value);
            }


            $reverter = ConverterFactory::getReverter($valueType, $value);

            if (!$reverter) {
                continue;
            }

            $revertedValue = $reverter->revert($value, $valueType);
            $object->{$key} = $revertedValue;
        }

        return $object;
    }

    private function redisArrayUnflatten(array $array)
    {
        $output = [];

        foreach ($array as $key => $value) {
            $keys = explode('.', $key);
            $current = &$output;

            foreach ($keys as $innerKey) {
                if (!isset($current[$innerKey])) {
                    $current[$innerKey] = [];
                } elseif (!is_array($current[$innerKey])) {
                    $current[$innerKey] = [$current[$innerKey]];
                }

                $current = &$current[$innerKey];
            }

            $current = $value;
        }

        return $output;
    }

    public function supportsReversion(string $type, mixed $value): bool
    {
        return class_exists($type) && $value !== 'null' && !in_array($type, AbstractDateTimeConverter::DATETYPES_NAMES);
    }
}
