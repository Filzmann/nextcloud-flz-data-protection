<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
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
