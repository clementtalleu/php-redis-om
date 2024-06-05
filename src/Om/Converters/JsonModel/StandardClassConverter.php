<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Converters\JsonModel;

use Talleu\RedisOm\Om\Converters\AbstractStandardClassConverter;
use Talleu\RedisOm\Om\Converters\HashModel\ArrayConverter;
use Talleu\RedisOm\Om\Converters\HashModel\ConverterFactory;
use Talleu\RedisOm\Om\Converters\HashModel\HashObjectConverter;

final class StandardClassConverter extends AbstractStandardClassConverter
{
    public function convert($data, ?array $hashData = [], ?string $parentProperty = null, ?string $parentPropertyType = null): array
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

            if ($converter instanceof JsonObjectConverter || $converter instanceof \Talleu\RedisOm\Om\Converters\JsonModel\ArrayConverter) {
                $convertedData[$key]['#type'] = $valueType;
            }
        }

        return $convertedData;
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
