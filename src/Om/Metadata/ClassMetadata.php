<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Metadata;

class ClassMetadata implements MetadataInterface
{
    public function __construct(
        public string $className,
        public ?array $identifier = [],
        public ?array $fieldsMapping = [],
        public ?array $associations = [],
        public ?array $typesFields = [],
    ) {
    }

    public function getName()
    {
        return $this->className;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getReflectionClass()
    {
        return new \ReflectionClass($this->className);
    }

    public function isIdentifier(string $fieldName)
    {
        foreach ($this->identifier as $identifier) {
            if ($identifier === $fieldName) {
                return true;
            }
        }

        return false;
    }

    public function hasField(string $fieldName)
    {
        foreach ($this->fieldsMapping as $field) {
            if ($field === $fieldName) {
                return true;
            }
        }

        return false;
    }

    public function hasAssociation(string $fieldName)
    {
        foreach ($this->associations as $association) {
            if ($association === $fieldName) {
                return true;
            }
        }

        return false;
    }

    public function isSingleValuedAssociation(string $fieldName)
    {
        foreach ($this->associations as $association) {
            if ($association === $fieldName) {
                return false;
            }
        }

        return true;
    }

    public function isCollectionValuedAssociation(string $fieldName)
    {
        return false;
    }

    public function getFieldNames()
    {
        return array_values($this->fieldsMapping);
    }

    public function getIdentifierFieldNames()
    {
        return array_values($this->identifier);
    }

    public function getAssociationNames()
    {
        return array_values($this->associations);
    }

    public function getTypeOfField(string $fieldName): ?string
    {
        foreach ($this->typesFields as $field => $type) {
            if ($field === $fieldName) {
                return $type;
            }
        }

        return null;
    }

    public function isAssociationInverseSide(string $assocName)
    {
        return false;
    }

    public function getIdentifierValues(object $object)
    {
        return array_values($this->identifier);
    }

    public function setIdentifier(array $identifier)
    {
        $this->identifier = $identifier;
    }

    public function setFieldsMapping(array $fields)
    {
        $this->fieldsMapping = $fields;
    }

    public function setAssociations(array $associations)
    {
        $this->associations = $associations;
    }

    public function setTypesFields(array $fields)
    {
        $this->typesFields = $fields;
    }
}
