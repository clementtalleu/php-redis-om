<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Metadata;

use Talleu\RedisOm\Om\Mapping\Entity;
use Talleu\RedisOm\Om\Mapping\Id;
use Talleu\RedisOm\Om\Mapping\Property;
use Talleu\RedisOm\Om\Mapping\Unique;

class MetadataFactory
{
    private ?\ReflectionClass $reflectionClass = null;

    public function createClassMetadata(string $className): ClassMetadata
    {
        $classMetadata = new ClassMetadata($className);
        $classMetadata->setIdentifier($this->buildIdentifier($className));
        $classMetadata->setFieldsMapping($this->buildFieldsMapping());
        $classMetadata->setAssociations($this->buildAssociations());
        $classMetadata->setTypesFields($this->buildTypesFields());
        $classMetadata->setUniqueConstraints($this->buildUniqueConstraints());

        return $classMetadata;
    }

    private function buildIdentifier(string $className): array
    {
        $this->reflectionClass = new \ReflectionClass($className);
        $properties = $this->reflectionClass->getProperties();
        $identifier = [];
        foreach ($properties as $property) {
            $attributeId = $property->getAttributes(Id::class);
            if ($attributeId !== []) {
                $identifier[] = $property->getName();
            }
        }

        return $identifier;
    }

    private function buildFieldsMapping(): array
    {
        $reflectionProperties = $this->reflectionClass->getProperties();
        $properties = [];
        foreach ($reflectionProperties as $property) {
            $attributeProperty = $property->getAttributes(Property::class);
            if ($attributeProperty !== []) {
                $properties[] = $property->getName();
            }
        }

        return $properties;
    }

    private function buildAssociations(): array
    {
        $associations = [];
        $properties = $this->reflectionClass->getProperties();
        foreach ($properties as $property) {
            $attributes = $property->getAttributes(Property::class);
            if ($attributes === []) {
                continue;
            }

            /** @var \ReflectionNamedType|null $propertyType */
            $propertyType = $property->getType();
            if ($propertyType === null || $propertyType->isBuiltin()) {
                continue;
            }

            $typeName = $propertyType->getName();
            if (!class_exists($typeName)) {
                continue;
            }

            $reflectionAssociation = new \ReflectionClass($typeName);
            $attributesMapping = $reflectionAssociation->getAttributes(Entity::class);
            if ($attributesMapping !== []) {
                $associations[] = $property->getName();
            }
        }

        return $associations;
    }

    private function buildTypesFields(): array
    {
        $fields = [];
        $properties = $this->reflectionClass->getProperties();
        foreach ($properties as $property) {
            /** @var \ReflectionNamedType|null $propertyType */
            $propertyType = $property->getType();
            $fields[$property->getName()] = $propertyType?->getName();
        }

        return $fields;
    }

    private function buildUniqueConstraints(): array
    {
        $constraints = [];

        foreach ($this->reflectionClass->getProperties() as $property) {
            if ($property->getAttributes(Unique::class) !== []) {
                $constraints[] = [$property->getName()];
            }
        }

        foreach ($this->reflectionClass->getAttributes(Unique::class) as $attr) {
            $unique = $attr->newInstance();
            if ($unique->properties !== []) {
                $constraints[] = $unique->properties;
            }
        }

        return $constraints;
    }
}
