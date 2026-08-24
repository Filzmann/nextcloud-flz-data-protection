<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\PublicApi\V1;

use InvalidArgumentException;

final class RetentionProviderDescriptor {
    public function __construct(
        private string $appId,
        private string $displayName,
        private string $contractVersion,
        private int $maxPageSize,
    ) {
        if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/', $appId) || $displayName === '') {
            throw new InvalidArgumentException('Invalid retention provider identity.');
        }
        if (!preg_match('/^[1-9][0-9]*\.[0-9]+$/', $contractVersion) || $maxPageSize < 1 || $maxPageSize > 1000) {
            throw new InvalidArgumentException('Invalid retention provider contract.');
        }
    }

    public function appId(): string { return $this->appId; }
    public function displayName(): string { return $this->displayName; }
    public function contractVersion(): string { return $this->contractVersion; }
    public function maxPageSize(): int { return $this->maxPageSize; }
}
