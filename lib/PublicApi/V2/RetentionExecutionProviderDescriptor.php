<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\PublicApi\V2;

use InvalidArgumentException;

final class RetentionExecutionProviderDescriptor {
    public function __construct(private string $appId, private string $displayName, private string $contractVersion, private int $maxBatchSize) {
        if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/', $appId) || $displayName === '' || $contractVersion !== '2.0' || $maxBatchSize < 1 || $maxBatchSize > 500) {
            throw new InvalidArgumentException('Invalid retention execution provider descriptor.');
        }
    }
    public function appId(): string { return $this->appId; }
    public function displayName(): string { return $this->displayName; }
    public function contractVersion(): string { return $this->contractVersion; }
    public function maxBatchSize(): int { return $this->maxBatchSize; }
}
