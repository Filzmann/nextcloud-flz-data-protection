<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$sourceFiles = [];
$sourceIterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(dirname(__DIR__) . '/lib', FilesystemIterator::SKIP_DOTS),
);
foreach ($sourceIterator as $sourceFile) {
    if ($sourceFile->isFile() && $sourceFile->getExtension() === 'php') {
        $sourceFiles[] = $sourceFile->getPathname();
    }
}
sort($sourceFiles);

foreach ($sourceFiles as $sourceFile) {
    try {
        token_get_all((string)file_get_contents($sourceFile), TOKEN_PARSE);
    } catch (ParseError $error) {
        throw new RuntimeException(sprintf(
            'PHP syntax check failed for %s: %s',
            substr($sourceFile, strlen(dirname(__DIR__)) + 1),
            $error->getMessage(),
        ), 0, $error);
    }
}

$tests = glob(__DIR__ . '/unit/*Test.php') ?: [];
sort($tests);

foreach ($tests as $test) {
    require $test;
}

echo "Filzmann Data Protection PHP tests passed.\n";
