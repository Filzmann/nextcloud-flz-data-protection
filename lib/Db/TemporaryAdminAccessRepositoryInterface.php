<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Db;

use DateTimeImmutable;

interface TemporaryAdminAccessRepositoryInterface {
    public function replaceActive(string $targetUid, string $grantedBy, DateTimeImmutable $startsAt, DateTimeImmutable $endsAt): array;
    public function revokeActive(string $targetUid, string $revokedBy, DateTimeImmutable $revokedAt): bool;
    public function activeFor(string $targetUid, DateTimeImmutable $at): ?array;
    public function history(): array;
    public function historyForUid(string $uid, int $limit, DateTimeImmutable $asOf): array;
    public function endedBefore(DateTimeImmutable $cutoff, int $limit, int $offset): array;
}
