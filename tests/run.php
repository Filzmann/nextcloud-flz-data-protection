<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$tests = glob(__DIR__ . '/unit/*Test.php') ?: [];
sort($tests);

foreach ($tests as $test) {
    require $test;
}

echo "Filzmann Data Protection PHP tests passed.\n";

