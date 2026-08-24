<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Model;

final class AggregateReport {
    /** @param array<string, ProviderReport> $providers */
    public function __construct(
        private array $providers,
        private bool $coverageDeclared = false,
        private string $discoveryStatus = 'complete',
    ) {
    }

    /** @return array<string, ProviderReport> */
    public function providers(): array {
        return $this->providers;
    }

    public function isRegistrySnapshotComplete(): bool {
        return $this->discoveryStatus === 'complete' && $this->allProviderReportsComplete();
    }

    public function isCoverageComplete(): bool {
        return $this->coverageDeclared && $this->isRegistrySnapshotComplete();
    }

    public function discoveryStatus(): string {
        return $this->discoveryStatus;
    }

    private function allProviderReportsComplete(): bool {
        foreach ($this->providers as $report) {
            if (!in_array($report->status(), ['complete', 'not_applicable'], true)) {
                return false;
            }
        }

        return $this->providers !== [];
    }
}
