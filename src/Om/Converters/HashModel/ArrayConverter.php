<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters\HashModel;

use Talleu\RedisOm\Om\Converters\AbstractArrayConverter;

final class ArrayConverter extends AbstractArrayConverter
{
    /**
     * @param array $data
     */
    public function convert($data, ?array $hashData = [], ?string $parentProperty = null): array
    {
        foreach ($data as $key => $value) {
            $valueType = is_object($value) ? get_class($value) : gettype($value);
            $converter = ConverterFactory::getConverter($valueType, $value);

            if (!$converter) {
                continue;
            }

            if ($converter instanceof HashObjectConverter || $converter instanceof ArrayConverter) {
                $propertyKey = $parentProperty ? sprintf("%s.%s", $parentProperty, $key) : $key;
                $hashData = $converter->convert(data: $value, hashData:  $hashData, parentProperty: $propertyKey);
                continue;
            }

            $convertedValue = $converter->convert($value);

            if ($parentProperty) {
                $hashData[sprintf("%s.%s", $parentProperty, $key)] = $convertedValue;

                if ('string' !== $valueType) {
                    $hashData[sprintf("%s.%s.#type", $parentProperty, $key)] = $valueType;
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
