<?php

declare(strict_types=1);

namespace  Talleu\RedisOm\Console;

use Talleu\RedisOm\Command\GenerateSchema;

final class Runner
{
    public static function generateSchema(string $dirPath): void
    {
        if (!is_dir($dirPath)) {
            // Not a valid directory absolute path, try to find the directory in the project root
            $dirPath = __DIR__ . '/../../../../../' . $dirPath;
            if (!is_dir($dirPath)) {
                throw new \InvalidArgumentException(sprintf("Directory %s not found", $dirPath));
            }
        }

        GenerateSchema::generateSchema($dirPath);
    }
}
