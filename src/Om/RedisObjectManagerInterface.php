<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ObjectRepository;

interface RedisObjectManagerInterface
{
    public function persist(object $object);

    public function remove(object $object);

    public function find(string $className, $id);

    public function clear();

    public function detach(object $object);

    public function refresh(object $object);

    public function flush();

    public function getRepository(string $className);

    public function getClassMetadata(string $className);

    public function getMetadataFactory();

    public function initializeObject(object $obj);

    public function contains(object $object);
}
