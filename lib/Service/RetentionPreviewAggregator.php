<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Service;

use OCA\FilzmannDataProtection\PublicApi\V1\RegisterRetentionProvidersEvent;
use OCA\FilzmannDataProtection\PublicApi\V1\RetentionPreviewRequest;
use OCP\EventDispatcher\IEventDispatcher;

final class RetentionPreviewAggregator {
    public function __construct(private IEventDispatcher $events) {}

    public function collect(string $evaluatedAt, int $limit): array {
        $event = new RegisterRetentionProvidersEvent();
        try {
            $this->events->dispatchTyped($event);
            $discoveryStatus = 'complete';
        } catch (\Throwable) {
            $discoveryStatus = 'failed';
        }

        $reports = [];
        foreach ($event->providers() as $appId => $provider) {
            $descriptor = $provider->descriptor();
            $policies = array_map(static fn($policy): array => $policy->toArray(), $provider->policies());
            $candidates = [];
            $warnings = [];
            $status = 'complete';
            try {
                foreach ($provider->policies() as $policy) {
                    $page = $provider->preview(new RetentionPreviewRequest(
                        $policy->policyId(),
                        $evaluatedAt,
                        min($limit, $descriptor->maxPageSize()),
                    ));
                    foreach ($page->candidates() as $candidate) {
                        if ($candidate->policyId() !== $policy->policyId() || $candidate->action() !== 'REVIEW') {
                            throw new \DomainException('Retention candidate does not match policy.');
                        }
                        $candidates[] = $candidate->toArray();
                    }
                    $warnings = [...$warnings, ...$page->warnings()];
                    if ($page->status() === 'partial') $status = 'partial';
                }
            } catch (\Throwable) {
                $status = 'failed';
                $candidates = [];
                $warnings = ['Provider-Vorschau fehlgeschlagen.'];
            }
            $reports[$appId] = [
                'displayName' => $descriptor->displayName(),
                'status' => $status,
                'policies' => $policies,
                'candidates' => $candidates,
                'warnings' => $warnings,
            ];
        }
        foreach ($event->registrationFailures() as $appId => $_failure) {
            $reports[$appId] = [
                'displayName' => $appId,
                'status' => 'failed',
                'policies' => [],
                'candidates' => [],
                'warnings' => ['Provider inkompatibel oder mehrdeutig.'],
            ];
        }
        ksort($reports);

        return ['evaluatedAt' => $evaluatedAt, 'discoveryStatus' => $discoveryStatus, 'providers' => $reports];
    }
}
