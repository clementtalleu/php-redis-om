<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Om\Mapping;

use Attribute;
use Talleu\RedisOm\Om\Converters\ConverterInterface;
use Talleu\RedisOm\Om\Converters\HashModel\HashObjectConverter;
use Talleu\RedisOm\Om\Converters\JsonModel\JsonObjectConverter;
use Talleu\RedisOm\Om\RedisFormat;
use Talleu\RedisOm\Om\Repository\HashModel\HashRepository;
use Talleu\RedisOm\Om\Repository\JsonModel\JsonRepository;
use Talleu\RedisOm\Om\Repository\RepositoryInterface;

#[Attribute(Attribute::TARGET_CLASS)]
final class Entity
{
    public function __construct(
        public ?string              $prefix = null,
        public ?string              $format = null,
        public ?ConverterInterface  $converter = null,
        public ?RepositoryInterface $repository = null,
    ) {
        $this->converter = $converter ?? ($format === RedisFormat::JSON->value ? new JsonObjectConverter() : new HashObjectConverter());
        $this->repository = $repository ?? ($format === RedisFormat::JSON->value ? new JsonRepository() : new HashRepository());
    }
}
