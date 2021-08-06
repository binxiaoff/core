<?php

declare(strict_types=1);

$baseDir = \dirname(__DIR__) . '/bin/.phpunit';

foreach (\array_diff(\scandir(\dirname(__DIR__) . '/bin/.phpunit'), ['..', '.']) as $installDir) {
    $phpUnitDir = $baseDir . '/' . $installDir;
    if (
        \is_dir($phpUnitDir)
        && 0 === \mb_strpos($installDir, 'phpunit-')
        && \is_file($phpUnitDir . '/' . 'vendor/autoload.php')
    ) {
        require $phpUnitDir . '/vendor/autoload.php';
    }
}
