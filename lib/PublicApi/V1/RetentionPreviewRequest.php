<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\PublicApi\V1;

use DateTimeImmutable;
use InvalidArgumentException;

final class RetentionPreviewRequest {
    public function __construct(
        private string $policyId,
        private string $evaluatedAt,
        private int $limit,
        private ?string $cursor = null,
    ) {
        if (!preg_match('/^[a-z][a-z0-9_-]{1,63}$/', $policyId) || $limit < 1 || $limit > 1000) {
            throw new InvalidArgumentException('Invalid retention preview request.');
        }
        try {
            $this->evaluatedAt = (new DateTimeImmutable($evaluatedAt))->format(DATE_ATOM);
        } catch (\Throwable) {
            throw new InvalidArgumentException('Invalid retention evaluation date.');
        }
        if ($cursor !== null && ($cursor === '' || strlen($cursor) > 2048)) {
            throw new InvalidArgumentException('Invalid retention preview cursor.');
        }
    }

    public function policyId(): string { return $this->policyId; }
    public function evaluatedAt(): string { return $this->evaluatedAt; }
    public function limit(): int { return $this->limit; }
    public function cursor(): ?string { return $this->cursor; }
}
