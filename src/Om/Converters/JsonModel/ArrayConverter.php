<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters\JsonModel;

use Talleu\RedisOm\Om\Converters\AbstractArrayConverter;

class ArrayConverter extends AbstractArrayConverter
{
    /**
     * @param array $data
     */
    public function convert($data): array
    {
        $convertedData = [];
        foreach ($data as $key => $value) {

            $valueType = is_object($value) ? get_class($value) : gettype($value);

            $converter = ConverterFactory::getConverter($valueType, $value);
            if (!$converter) {
                continue;
            }

            $convertedValue = $converter->convert($value);
            $convertedData[$key] = $convertedValue;

            if ($converter instanceof JsonObjectConverter || $converter instanceof ArrayConverter) {
                $convertedData[$key]['#type'] = $valueType;
            }
        }

        return $convertedData;
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

    public function supportsReversion(string $type, mixed $value): bool
    {
        return $type === 'array' && $value !== null;
    }
}
