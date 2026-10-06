<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Service;

use DateTimeImmutable;
use OCA\FilzmannDataProtection\PublicApi\V2\RegisterRetentionExecutionProvidersEvent;
use OCA\FilzmannDataProtection\PublicApi\V2\RetentionExecutionBatch;
use OCA\FilzmannDataProtection\PublicApi\V2\RetentionExecutionRequest;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;
use Throwable;

final class RetentionExecutionCoordinator {
    public function __construct(
        private IEventDispatcher $events,
        private RetentionExecutionProfileStatus $profile,
        private LoggerInterface $logger,
    ) {
    }

    public function run(?string $evaluatedAt = null): array {
        $status = $this->profile->status();
        if (($status['executionAvailable'] ?? false) !== true) {
            return ['status' => 'blocked', 'providers' => []];
        }
        $approved = $status['configuration']['approvedPolicyIds'] ?? [];
        if (!is_array($approved) || $approved === []) {
            return ['status' => 'blocked', 'providers' => []];
        }
        $at = (new DateTimeImmutable($evaluatedAt ?? 'now'))->format(DATE_ATOM);
        $event = new RegisterRetentionExecutionProvidersEvent();
        try {
            $this->events->dispatchTyped($event);
        } catch (Throwable $error) {
            $this->logger->error('Retention execution provider discovery failed.', [
                'stage' => 'provider_discovery',
                'error_type' => get_debug_type($error),
            ]);
            return [
                'status' => 'failed',
                'diagnosticCode' => 'provider_discovery_failed',
                'providers' => [],
            ];
        }
        $reports = [];
        $providers = [];
        $covered = [];
        $failed = false;
        foreach ($event->providers() as $appId => $provider) {
            $reports[$appId] = ['status'=>'complete','policies'=>[]];
            try {
                $descriptor = $provider->descriptor();
                $policies = $provider->policies();
                $providers[$appId] = compact('provider', 'descriptor', 'policies');
                foreach ($policies as $policy) {
                    $approvedPolicyId = $appId . ':' . $policy->policyId();
                    if (in_array($approvedPolicyId, $approved, true)) {
                        $covered[$approvedPolicyId] = true;
                    }
                }
            } catch (Throwable $error) {
                $failed = true;
                $reports[$appId] = ['status'=>'failed','policies'=>[]];
                $this->logger->error('Retention execution provider failed.', [
                    'stage' => 'provider_execution',
                    'provider_app_id' => $appId,
                    'error_type' => get_debug_type($error),
                ]);
            }
        }
        foreach ($event->registrationFailures() as $appId => $_failure) {
            $failed = true;
            $reports[$appId] = ['status'=>'failed','policies'=>[]];
            $this->logger->error('Retention execution provider registration failed.', [
                'stage' => 'provider_registration',
                'provider_app_id' => $appId,
            ]);
        }
        if ($failed) {
            ksort($reports);
            return [
                'status' => 'failed',
                'diagnosticCode' => 'provider_execution_failed',
                'providers' => $reports,
            ];
        }
        $missing = array_values(array_diff($approved, array_keys($covered)));
        if ($missing !== []) {
            $this->logger->error('Retention execution provider coverage is incomplete.', [
                'stage' => 'provider_coverage',
                'missing_count' => count($missing),
            ]);
            return [
                'status' => 'blocked',
                'diagnosticCode' => 'provider_coverage_incomplete',
                'providers' => $reports,
            ];
        }
        foreach ($providers as $appId => $snapshot) {
            try {
                foreach ($snapshot['policies'] as $policy) {
                    if (!in_array($appId . ':' . $policy->policyId(), $approved, true)) continue;
                    $provider = $snapshot['provider'];
                    $descriptor = $snapshot['descriptor'];
                    $request = new RetentionExecutionRequest($policy->policyId(), $policy->version(), $at, min(100, $descriptor->maxBatchSize()));
                    $page = $provider->plan($request);
                    $result = $provider->execute(new RetentionExecutionBatch($request, $page->candidates()));
                    $failedReferences = count($result->failedReferences());
                    $reports[$appId]['policies'][$policy->policyId()] = [
                        'deleted'=>count($result->deletedReferences()),
                        'held'=>count(array_unique([...$page->heldReferences(), ...$result->heldReferences()])),
                        'stale'=>count($result->staleReferences()),
                        'failed'=>$failedReferences,
                        'warnings'=>$page->warnings(),
                    ];
                    if ($failedReferences > 0) {
                        $failed = true;
                        $reports[$appId]['status'] = 'failed';
                        $this->logger->error('Retention execution provider reported failed candidates.', [
                            'stage' => 'provider_execution',
                            'provider_app_id' => $appId,
                            'policy_id' => $policy->policyId(),
                            'failed_count' => $failedReferences,
                        ]);
                    }
                }
            } catch (Throwable $error) {
                $failed = true;
                $reports[$appId] = ['status'=>'failed','policies'=>[]];
                $this->logger->error('Retention execution provider failed.', [
                    'stage' => 'provider_execution',
                    'provider_app_id' => $appId,
                    'error_type' => get_debug_type($error),
                ]);
            }
        }
        ksort($reports);
        if ($failed) {
            return [
                'status' => 'failed',
                'diagnosticCode' => 'provider_execution_failed',
                'providers' => $reports,
            ];
        }
        return ['status'=>'complete','providers'=>$reports];
    }
}
