<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters\HashModel;

use Talleu\RedisOm\Om\Converters\AbstractStandardClassConverter;

final class StandardClassConverter extends AbstractStandardClassConverter
{
    public function convert($data, ?array $hashData = [], ?string $parentProperty = null, ?string $parentPropertyType = null): array
    {
        foreach ($data as $key => $value) {

            $valueType = is_object($value) ? get_class($value) : gettype($value);
            $converter = ConverterFactory::getConverter($valueType, $value);

            if (!$converter) {
                continue;
            }

            if ($converter instanceof HashObjectConverter || $converter instanceof ArrayConverter || $converter instanceof StandardClassConverter) {
                $propertyKey = $parentProperty ? "$parentProperty.$key" : $key;
                $hashData["$parentProperty.$key.#type"] = $valueType;
                $hashData = $converter->convert(data: $value, hashData:  $hashData, parentProperty: $propertyKey, parentPropertyType: $valueType);
                continue;
            }

            $convertedValue = $converter->convert($value);

            if ($parentProperty) {
                $hashData["$parentProperty.$key"] = $convertedValue;

                if ('string' !== $valueType) {
                    $hashData["$parentProperty.$key.#type"] = $valueType;
                }

                continue;
            }

            $hashData[$key] = $convertedValue;
        }

        return $hashData;
    }

    public function revert($data, string $type): \stdClass
    {
        $revertedStdClass = new \stdClass();
        foreach ($data as $key => $value) {

            if (is_array($value) && array_key_exists('#type', $value)) {
                $valueType = $value['#type'];
                unset($value['#type']);
            } else {
                $valueType = gettype($value);
            }
            $reverter = ConverterFactory::getReverter($valueType, $value);
            if (!$reverter) {
                continue;
            }

            $revertedStdClass->{$key} = $reverter->revert($value, $valueType);
        }

        return $revertedStdClass;
    }
}
