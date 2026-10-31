<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Service;

interface DataProtectionPermissionChecker {
    public function has(string $permission): bool;
}
