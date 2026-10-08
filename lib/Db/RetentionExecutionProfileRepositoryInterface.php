<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Db;

interface RetentionExecutionProfileRepositoryInterface {
    public function latest(): ?array;

    /** @return list<array<string, mixed>> */
    public function history(): array;

    /** @return list<array<string, mixed>> */
    public function historyForUid(string $uid, int $limit, int $offset, \DateTimeImmutable $asOf): array;

    /** @param array<string, mixed> $configuration */
    public function append(array $configuration): array;
}
