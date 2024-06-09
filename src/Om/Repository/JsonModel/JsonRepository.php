<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Repository\JsonModel;

use Talleu\RedisOm\Exception\BadPropertyConfigurationException;
use Talleu\RedisOm\Exception\BadPropertyException;
use Talleu\RedisOm\Om\Converters\JsonModel\ConverterFactory;
use Talleu\RedisOm\Om\RedisFormat;
use Talleu\RedisOm\Om\Repository\AbstractObjectRepository;

final class JsonRepository extends AbstractObjectRepository
{
    public ?string $format = RedisFormat::JSON->value;

    public function find($identifier): ?object
    {
        $data = $this->redisClient->jsonget("$this->prefix:$identifier");
        if (!$data) {
            return null;
        }

        return $this->converter->revert(\json_decode($data, true), $this->className);
    }

    public function getPropertyValue($identifier, string $property): mixed
    {
        try {
            $reflectionProperty = new \ReflectionProperty($this->className, $property);
        } catch (\ReflectionException $e) {
            throw new BadPropertyException("Property $property does not exist for class $this->className");
        }

        $result = $this->redisClient->jsonGetProperty("$this->prefix:$identifier", $property);
        if (!$result) {
            return null;
        }

        $data = json_decode($result, true)[0];

        if (is_array($data) && array_key_exists('#type', $data)) {
            $valueType = $data['#type'];
        } elseif ($reflectionProperty->hasType()) {
            /** @var \ReflectionNamedType $propertyType */
            $propertyType = $reflectionProperty->getType();
            $valueType = $propertyType->getName();
        } else {
            $valueType = gettype($data);
        }

        $reverter = ConverterFactory::getReverter($valueType, $data);

        return $reverter->revert($data, $valueType);
    }
}
