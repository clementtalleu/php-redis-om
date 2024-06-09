<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Metadata;

interface MetadataInterface
{
    public function getName();

    public function getIdentifier();

    public function getReflectionClass();

    public function isIdentifier(string $fieldName);

    public function hasField(string $fieldName);

    public function hasAssociation(string $fieldName);

    public function isSingleValuedAssociation(string $fieldName);

    public function isCollectionValuedAssociation(string $fieldName);

    public function getFieldNames();

    public function getIdentifierFieldNames();

    public function getAssociationNames();

    public function getTypeOfField(string $fieldName);

    public function isAssociationInverseSide(string $assocName);

    public function getIdentifierValues(object $object);
}
