<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters\HashModel;

use Talleu\RedisOm\Om\Converters\AbstractArrayConverter;

final class ArrayConverter extends AbstractArrayConverter
{
    /**
     * @param array $data
     */
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

            if ($converter instanceof DateTimeConverter || $converter instanceof DateTimeImmutableConverter) {
                $hashData[($parentProperty ? "$parentProperty." : '').$key.'#timestamp'] = strtotime($convertedValue);
            }

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

    public function revert($data, string $type): mixed
    {
        $revertedArray = [];
        foreach ($data as $key => $value) {

            if (is_array($value) && array_key_exists('#type', $value)) {
                $valueType = $value['#type'];
                unset($value['#type']);
            } elseif (str_contains((string) $key, '#timestamp')) {
                continue;
            } else {
                $valueType = gettype($value);
            }

            $reverter = ConverterFactory::getReverter($valueType, $value);
            if (!$reverter) {
                continue;
            }

            $revertedArray[$key] = $reverter->revert($value, $valueType);
        }

        return $revertedArray;
    }
}
