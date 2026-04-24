<?php

declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Repository\JsonModel;

use PHPUnit\Framework\TestCase;

/**
 * Range queries on JSON require explicit NUMERIC index.
 * JSON stores int values as strings via ScalarConverter, incompatible with auto NUMERIC.
 * See HashModel\RangeQueryTest for HASH range query tests.
 */
final class RangeQueryTest extends TestCase
{
    public function testJsonRangeQueriesRequireExplicitNumericIndex(): void
    {
        $this->markTestSkipped('Range queries on JSON require explicit NUMERIC index. Use HASH or #[Property(index: [\'field\' => \'NUMERIC\'])].');
    }
}
