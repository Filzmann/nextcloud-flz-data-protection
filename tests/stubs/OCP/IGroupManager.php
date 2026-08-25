<?php

declare(strict_types=1);

namespace OCP;

interface IGroupManager {
    public function isAdmin(string $uid): bool;
    public function isInGroup(string $uid, string $gid): bool;
    public function groupExists(string $gid): bool;
}
