<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters\HashModel;

use Talleu\RedisOm\Om\Converters\AbstractDateTimeConverter;
use Talleu\RedisOm\Om\Converters\AbstractObjectConverter;
use Talleu\RedisOm\Om\Mapper\ClassMapper;
use Talleu\RedisOm\Om\Mapper\ConstructorMapper;
use Talleu\RedisOm\Om\Mapper\SetterMapper;
use Talleu\RedisOm\Om\Mapping\Property;

final class HashObjectConverter extends AbstractObjectConverter
{
    public function convert($data, ?array $hashData = [], ?string $parentProperty = null, ?string $parentPropertyType = null): array
    {
        $reflection = new \ReflectionClass($data);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE);

        foreach ($properties as $property) {

            /** @var Property|null $propertyAttribute */
            $propertyAttribute = $property->getAttributes(Property::class) !== [] ? $property->getAttributes(Property::class)[0]->newInstance() : null;
            if (is_null($propertyAttribute)) {
                continue;
            }

            $value = $this->extractPropertyValue($propertyAttribute, $property, $data);

            /** @var \ReflectionNamedType|null $propertyType */
            $propertyType = $property->getType();
            $valueType = $property->hasType() ? $propertyType->getName() : (is_object($value) ? get_class($value) : gettype($value));

            $converter = ConverterFactory::getConverter($valueType, $value);
            if (!$converter) {
                continue;
            }

            if ($converter instanceof HashObjectConverter || $converter instanceof ArrayConverter || $converter instanceof StandardClassConverter) {
                $propertyName = $parentProperty ? "$parentProperty.{$property->getName()}" : $property->getName();
                $hashData = $converter->convert(data: $value, hashData: $hashData, parentProperty: $propertyName, parentPropertyType: $valueType);
                continue;
            }

            $convertedValue = $converter->convert($value);

            if ($converter instanceof DateTimeConverter || $converter instanceof DateTimeImmutableConverter) {
                $hashData[($parentProperty ? "$parentProperty." : '') . $property->getName() . '#timestamp'] = strtotime($convertedValue);
            }

            if ($parentProperty) {
                $hashData["$parentProperty.{$property->getName()}"] = $convertedValue;
                if ($parentPropertyType !== null) {
                    $hashData["$parentProperty.#type"] = $parentPropertyType;
                }

                continue;
            }

            $hashData[$property->getName()] = $convertedValue;
        }

        return $hashData;
    }


    public function revert($data, string $type): mixed
    {
        $data = $this->redisArrayUnflatten($data);

        $fieldsWithValue = [];
        foreach ($data as $key => $value) {

            if (!property_exists($type, $key)) {
                continue;
            }

            $reflectionProperty = new \ReflectionProperty($type, $key);
            if (is_array($value) && array_key_exists('#type', $value)) {
                $valueType = $value['#type'];
            } elseif ($reflectionProperty->getType() && $value !== null) {
                /** @var \ReflectionNamedType|null $propertyType */
                $propertyType = $reflectionProperty->getType();
                $valueType = $propertyType->getName();
            } else {
                $valueType = is_object($value) ? get_class($value) : gettype($value);
            }

            $reverter = ConverterFactory::getReverter($valueType, $value);
            if (!$reverter) {
                continue;
            }

            $revertedValue = $reverter->revert($value, $valueType);

            $fieldsWithValue[$key] = $revertedValue;
        }
        return $this->assignValue($type, $fieldsWithValue);

    }

    private function redisArrayUnflatten(array $data)
    {
        $output = [];

        foreach ($data as $key => $value) {
            $keys = explode('.', $key);

            /** @var array<string, mixed> $current */
            $current = &$output;

            foreach ($keys as $innerKey) {
                if (!isset($current[$innerKey])) {
                    $current[$innerKey] = [];
                } elseif (!is_array($current[$innerKey])) {
                    $current[$innerKey] = [$current[$innerKey]];
                }

                $current = &$current[$innerKey];
            }

            $current = $value !== 'null' ? $value : null;
        }

        return $output;
    }

    public function supportsReversion(string $type, mixed $value): bool
    {
        return class_exists($type) && $type !== \stdClass::class && $value !== 'null' && !in_array($type, AbstractDateTimeConverter::DATETYPES_NAMES);
    }
}
