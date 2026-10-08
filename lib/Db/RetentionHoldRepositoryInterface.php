<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Db;

use DateTimeImmutable;

interface RetentionHoldRepositoryInterface {
    public function activeFor(string $policyId,string $recordReference):?array;
    public function place(string $policyId,string $recordReference,string $reasonCode,string $evidenceReference,string $placedBy,DateTimeImmutable $placedAt,DateTimeImmutable $reviewDueAt):int;
    public function releaseActive(string $policyId,string $recordReference,string $releasedBy,DateTimeImmutable $releasedAt):bool;
}
