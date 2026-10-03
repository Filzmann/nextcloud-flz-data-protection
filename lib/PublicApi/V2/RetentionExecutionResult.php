<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\PublicApi\V2;

use InvalidArgumentException;

final class RetentionExecutionResult {
    /** @param list<string> $deletedReferences @param list<string> $heldReferences @param list<string> $staleReferences @param list<string> $failedReferences */
    public function __construct(private array $deletedReferences, private array $heldReferences, private array $staleReferences, private array $failedReferences) {
        foreach ([...$deletedReferences, ...$heldReferences, ...$staleReferences, ...$failedReferences] as $reference) {
            if (!is_string($reference) || $reference === '' || strlen($reference) > 255) throw new InvalidArgumentException('Invalid execution result reference.');
        }
    }
    public function deletedReferences(): array { return $this->deletedReferences; }
    public function heldReferences(): array { return $this->heldReferences; }
    public function staleReferences(): array { return $this->staleReferences; }
    public function failedReferences(): array { return $this->failedReferences; }
}
