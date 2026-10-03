<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\PublicApi\V2;

use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;

final class RetentionExecutionRequest {
    public function __construct(private string $policyId, private string $policyVersion, private string $evaluatedAt, private int $limit) {
        if (!preg_match('/^[a-z][a-z0-9_-]{1,63}$/', $policyId) || !preg_match('/^2\.[0-9]+$/', $policyVersion) || $limit < 1 || $limit > 500) {
            throw new InvalidArgumentException('Invalid retention execution request.');
        }
        try { $this->evaluatedAt = (new DateTimeImmutable($evaluatedAt))->format(DATE_ATOM); }
        catch (Throwable $error) { throw new InvalidArgumentException('Invalid retention evaluation date.', 0, $error); }
    }
    public function policyId(): string { return $this->policyId; }
    public function policyVersion(): string { return $this->policyVersion; }
    public function evaluatedAt(): string { return $this->evaluatedAt; }
    public function limit(): int { return $this->limit; }
}
