<?php

$autoloadFiles = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php'
];

foreach ($autoloadFiles as $autoloadFile) {
    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;
        break;
    }
}

$dirPath = array_key_exists(1, $argv) ? $argv[1] : 'src';

\Talleu\RedisOm\Console\Runner::generateSchema($dirPath);