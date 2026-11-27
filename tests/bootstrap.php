<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $organizationPrefix = 'OCA\\FlzOrganization\\PublicApi\\V1\\';
    if (str_starts_with($class, $organizationPrefix)) {
        $stubPath = __DIR__ . '/stubs/FlzOrganization/' . substr($class, strlen($organizationPrefix)) . '.php';
        if (is_file($stubPath)) {
            require $stubPath;
        }
        return;
    }

    $psrPrefix = 'Psr\\';
    if (str_starts_with($class, $psrPrefix)) {
        $stubPath = __DIR__ . '/stubs/Psr/' . str_replace('\\', '/', substr($class, strlen($psrPrefix))) . '.php';
        if (is_file($stubPath)) {
            require $stubPath;
        }
        return;
    }

    $stubPrefix = 'OCP\\';
    if (str_starts_with($class, $stubPrefix)) {
        $stubPath = __DIR__ . '/stubs/OCP/' . str_replace('\\', '/', substr($class, strlen($stubPrefix))) . '.php';
        if (is_file($stubPath)) {
            require $stubPath;
        }
        return;
    }

    $testsPrefix = 'OCA\\FlzDataProtection\\Tests\\';
    if (str_starts_with($class, $testsPrefix)) {
        $testsPath = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($testsPrefix))) . '.php';
        if (is_file($testsPath)) {
            require $testsPath;
        }
        return;
    }

    $prefix = 'OCA\\FlzDataProtection\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = dirname(__DIR__) . '/lib/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});
