<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures;

use Talleu\RedisOm\Om\Mapping as RedisOm;

abstract class AbstractBook
{
    #[RedisOm\Id]
    #[RedisOm\Property]
    public ?string $isbn = null;

    #[RedisOm\Property]
    public ?string $title = null;

    #[RedisOm\Property]
    public ?string $description = null;

    #[RedisOm\Property]
    public ?\DateTime $createdAt = null;

    #[RedisOm\Property]
    public ?array $comments = [];

    #[RedisOm\Property]
    public ?iterable $tags = null;

    public static function create(
        ?string $isbn,
        ?string $title,
        ?string $description,
        ?\DateTime $createdAt = null,
        ?array $comments = [],
        ?iterable $tags = null,
    ): static {
        $book = new static();
        $book->isbn = $isbn;
        $book->title = $title;
        $book->description = $description;
        $book->createdAt = $createdAt;
        $book->comments = $comments;
        $book->tags = $tags;

        return $book;
    }
}