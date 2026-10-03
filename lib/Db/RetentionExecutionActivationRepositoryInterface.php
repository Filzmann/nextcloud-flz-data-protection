<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Db;

interface RetentionExecutionActivationRepositoryInterface {
    /** @return array<string, mixed>|null */
    public function latest(): ?array;

    /** @return list<array<string, mixed>> */
    public function history(): array;

    /** @return list<array<string, mixed>> */
    public function historyForUid(string $uid, int $limit, int $offset, \DateTimeImmutable $asOf): array;

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, mixed>
     */
    public function appendIfCurrent(array $configuration, int $expectedRevision): array;
}
