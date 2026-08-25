<?php

declare(strict_types=1);

namespace OCP;

interface IAppConfig {
    public function getValueArray(string $appId, string $key, array $default = [], bool $lazy = false): array;
    public function getValueBool(string $appId, string $key, bool $default = false, bool $lazy = false): bool;
    public function setValueArray(string $appId, string $key, array $value, bool $lazy = false): void;
    public function setValueBool(string $appId, string $key, bool $value, bool $lazy = false): void;
}
