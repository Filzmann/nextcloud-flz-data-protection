<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Model;

final class AggregateReport {
    /** @param array<string, ProviderReport> $providers */
    public function __construct(private array $providers) {
    }

    /** @return array<string, ProviderReport> */
    public function providers(): array {
        return $this->providers;
    }

    public function isRegistrySnapshotComplete(): bool {
        foreach ($this->providers as $report) {
            if (!in_array($report->status(), ['complete', 'not_applicable'], true)) {
                return false;
            }
        }

        return $this->providers !== [];
    }
}
