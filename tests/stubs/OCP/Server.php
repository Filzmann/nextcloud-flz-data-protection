<?php

declare(strict_types=1);

namespace OCP;

final class Server
{
    public static array $services = [];
    public static int $queries = 0;

    public static function get(string $class): mixed
    {
        self::$queries++;
        return self::$services[$class] ?? throw new \RuntimeException('Synthetic provider unavailable.');
    }
}
