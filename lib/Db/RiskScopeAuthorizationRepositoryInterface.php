<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Db;

interface RiskScopeAuthorizationRepositoryInterface {
    /** @return array<string, mixed>|null */
    public function latest(string $scopeId): ?array;

    /** @return list<array<string, mixed>> */
    public function history(string $scopeId): array;

    /**
     * Appends revision expectedRevision + 1 or rejects a stale writer.
     *
     * @param array<string, mixed> $configuration
     * @return array<string, mixed>
     */
    public function appendIfCurrent(array $configuration, int $expectedRevision): array;
}
