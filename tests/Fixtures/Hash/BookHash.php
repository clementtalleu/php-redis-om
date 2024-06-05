<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Fixtures\Hash;

use Talleu\RedisOm\Om\Mapping as RedisOm;
use Talleu\RedisOm\Tests\Fixtures\AbstractBook;

#[RedisOm\Entity]
class BookHash extends AbstractBook
{
    #[RedisOm\Property]
    public ?UserHash $user = null;
}
