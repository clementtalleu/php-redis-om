<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters\JsonModel;

use Talleu\RedisOm\Om\Converters\AbstractDateTimeConverter;
use Talleu\RedisOm\Om\Converters\AbstractObjectConverter;
use Talleu\RedisOm\Om\Mapping\Property;

final class JsonObjectConverter extends AbstractObjectConverter
{
    /**
     * @param object $data
     */
    public function convert($data): array
    {
        $reflection = new \ReflectionClass($data);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE);
        $convertedData = [];

        foreach ($properties as $property) {

            $propertyAttribute = $property->getAttributes(Property::class) !== [] ? $property->getAttributes(Property::class)[0]->newInstance() : null;
            if (!$propertyAttribute) {
                continue;
            }

            $value = $this->extractPropertyValue($propertyAttribute, $property, $data);

            /** @var \ReflectionNamedType|null $propertyType */
            $propertyType = $property->getType();
            $valueType = $propertyType ? $propertyType->getName() : (is_object($value) ? get_class($value) : gettype($value));

            $converter = ConverterFactory::getConverter($valueType, $value);
            if (!$converter) {
                continue;
            }

            $convertedValue = $converter->convert($value);
            if ($converter instanceof JsonObjectConverter || $converter instanceof ArrayConverter || $converter instanceof StandardClassConverter) {
                $convertedData[$property->getName()]['#type'] = $valueType;
            }

            $convertedData[$property->getName()] = $convertedValue;
        }

        return $convertedData;
    }


    /**
     * @param array $data
     */
    public function revert($data, string $type): mixed
    {
        $object = new $type();

        foreach ($data as $key => $value) {

            if (!property_exists($object, $key)) {
                continue;
            }

            $reflectionProperty = new \ReflectionProperty($type, $key);
            if (is_array($value) && array_key_exists('#type', $value)) {
                $valueType = $value['#type'];
            } elseif ($reflectionProperty->hasType()) {
                /** @var \ReflectionNamedType $propertyType */
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
            $this->assignValue($object, $key, $revertedValue, $type, $reflectionProperty);
        }

        return $object;
    }

    public function supportsReversion(string $type, mixed $value): bool
    {
        return $value !== null && $value !== 'null' && class_exists($type) && $type !== 'stdClass' && !in_array($type, AbstractDateTimeConverter::DATETYPES_NAMES);
    }
}
