<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\PublicApi\V2;

use InvalidArgumentException;

final class RetentionExecutionBatch {
    /** @param list<RetentionExecutionCandidate> $candidates */
    public function __construct(private RetentionExecutionRequest $request, private array $candidates) {
        foreach ($candidates as $candidate) {
            if (!$candidate instanceof RetentionExecutionCandidate
                || $candidate->policyId() !== $request->policyId()
                || $candidate->policyVersion() !== $request->policyVersion()) {
                throw new InvalidArgumentException('Execution batch does not match its dry run.');
            }
        }
    }
    public function request(): RetentionExecutionRequest { return $this->request; }
    public function candidates(): array { return $this->candidates; }
}
