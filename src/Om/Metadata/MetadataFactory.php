<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Metadata;

use Talleu\RedisOm\Om\Mapping\Entity;
use Talleu\RedisOm\Om\Mapping\Id;
use Talleu\RedisOm\Om\Mapping\Property;

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

            $associationClassName = $attributes[0]->getName();
            $reflectionAssociation = new \ReflectionClass($associationClassName);
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
}
