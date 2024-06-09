<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Console;

use Talleu\RedisOm\Console\Runner;
use Talleu\RedisOm\Tests\RedisAbstractTestCase;

class RunnerTest extends RedisAbstractTestCase
{
    public function testRunnerBadDir()
    {
        $this->expectException(\InvalidArgumentException::class);
        Runner::generateSchema('../bad_dir');
    }
}
