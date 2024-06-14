<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Repository\HashModel;

use Talleu\RedisOm\Exception\BadPropertyException;
use Talleu\RedisOm\Om\Converters\HashModel\ConverterFactory;
use Talleu\RedisOm\Om\RedisFormat;
use Talleu\RedisOm\Om\Repository\AbstractObjectRepository;

final class HashRepository extends AbstractObjectRepository
{
    public ?string $format = RedisFormat::HASH->value;

    /**
     * @inheritdoc
     */
    public function find($identifier): ?object
    {
        $data = $this->redisClient->hGetAll("$this->prefix:$identifier");
        if (!$data) {
            return null;
        }

        return $this->converter->revert($data, $this->className);
    }

    /**
     * @inheritdoc
     */
    public function getPropertyValue($identifier, string $property): mixed
    {
        try {
            $reflectionProperty = new \ReflectionProperty($this->className, $property);
        } catch (\ReflectionException $e) {
            throw new BadPropertyException("Property $property does not exist for class $this->className");
        }

        if (!$reflectionProperty->hasType()) {
            throw new BadPropertyException("Property $property for class $this->className does not have a type");
        }

        /** @var \ReflectionNamedType $propertyType */
        $propertyType = $reflectionProperty->getType();
        $propertyTypeName = $propertyType->getName();
        if (!in_array($propertyTypeName, ['int', 'string', 'float', 'bool', 'DateTime', 'DateTimeImmutable'])) {
            throw new BadPropertyException("Property $property for class $this->className of type $propertyTypeName is not supported for now, only sclar or Datetime");
        }

        $data = $this->redisClient->hget("$this->prefix:$identifier", $property);
        if (!$data) {
            return null;
        }

        $reverter = ConverterFactory::getReverter($propertyTypeName, $data);

        return $reverter->revert($data, $propertyTypeName);
    }
}
