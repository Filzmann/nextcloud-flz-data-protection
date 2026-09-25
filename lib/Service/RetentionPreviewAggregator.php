<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Service;

use DateTimeImmutable;
use InvalidArgumentException;
use OCA\FilzmannDataProtection\PublicApi\V1\RegisterRetentionProvidersEvent;
use OCA\FilzmannDataProtection\PublicApi\V1\RetentionPreviewRequest;
use OCP\EventDispatcher\IEventDispatcher;

final class RetentionPreviewAggregator {
    public function __construct(private IEventDispatcher $events) {}

    public function collect(string $evaluatedAt, int $limit, ?string $continuation = null): array {
        $target = $continuation === null ? null : $this->decodeContinuation($continuation);
        if ($target !== null) $evaluatedAt = $target['evaluatedAt'];

        $event = new RegisterRetentionProvidersEvent();
        try {
            $this->events->dispatchTyped($event);
            $discoveryStatus = 'complete';
        } catch (\Throwable) {
            $discoveryStatus = 'failed';
        }

        $reports = [];
        foreach ($event->providers() as $appId => $provider) {
            if ($target !== null && $target['appId'] !== $appId) continue;
            $descriptor = null;
            try {
                $descriptor = $provider->descriptor();
                $providerPolicies = $provider->policies();
                if ($target !== null) {
                    $providerPolicies = array_values(array_filter(
                        $providerPolicies,
                        static fn($policy): bool => $policy->policyId() === $target['policyId'],
                    ));
                    if ($providerPolicies === []) throw new InvalidArgumentException('Retention continuation policy unavailable.');
                }
                $policies = array_map(static fn($policy): array => $policy->toArray(), $providerPolicies);
                $candidates = [];
                $warnings = [];
                $continuations = [];
                $status = 'complete';
                foreach ($providerPolicies as $policy) {
                    $page = $provider->preview(new RetentionPreviewRequest(
                        $policy->policyId(),
                        $evaluatedAt,
                        min($limit, $descriptor->maxPageSize()),
                        $target['providerCursor'] ?? null,
                    ));
                    foreach ($page->candidates() as $candidate) {
                        if ($candidate->policyId() !== $policy->policyId() || $candidate->action() !== 'REVIEW') {
                            throw new \DomainException('Retention candidate does not match policy.');
                        }
                        $candidates[] = $candidate->toArray();
                    }
                    $warnings = [...$warnings, ...$page->warnings()];
                    if ($page->status() === 'partial') {
                        $status = 'partial';
                        $continuations[$policy->policyId()] = $this->encodeContinuation(
                            $appId,
                            $policy->policyId(),
                            $evaluatedAt,
                            (string)$page->nextCursor(),
                        );
                    } elseif ($page->status() === 'not_applicable' && $status === 'complete') {
                        $status = 'not_applicable';
                    }
                }
                $reports[$appId] = [
                    'displayName' => $descriptor->displayName(),
                    'status' => $status,
                    'policies' => $policies,
                    'candidates' => $candidates,
                    'warnings' => $warnings,
                    'continuations' => $continuations,
                ];
            } catch (\Throwable) {
                $reports[$appId] = [
                    'displayName' => $descriptor?->displayName() ?? $appId,
                    'status' => 'failed',
                    'policies' => [],
                    'candidates' => [],
                    'warnings' => ['Provider-Vorschau fehlgeschlagen.'],
                    'continuations' => [],
                ];
            }
        }
        foreach ($event->registrationFailures() as $appId => $_failure) {
            $reports[$appId] = [
                'displayName' => $appId,
                'status' => 'failed',
                'policies' => [],
                'candidates' => [],
                'warnings' => ['Provider inkompatibel oder mehrdeutig.'],
                'continuations' => [],
            ];
        }
        ksort($reports);

        if ($target !== null && !isset($reports[$target['appId']])) {
            throw new InvalidArgumentException('Retention continuation provider unavailable.');
        }

        return [
            'evaluatedAt' => $evaluatedAt,
            'discoveryStatus' => $discoveryStatus,
            'coverageComplete' => false,
            'providers' => $reports,
        ];
    }

    private function encodeContinuation(string $appId, string $policyId, string $evaluatedAt, string $providerCursor): string {
        $payload = json_encode(compact('appId', 'policyId', 'evaluatedAt', 'providerCursor'), JSON_THROW_ON_ERROR);
        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    }

    /** @return array{appId:string,policyId:string,evaluatedAt:string,providerCursor:string} */
    private function decodeContinuation(string $continuation): array {
        if ($continuation === '' || strlen($continuation) > 4096) throw new InvalidArgumentException('Invalid retention continuation.');
        $padding = (4 - strlen($continuation) % 4) % 4;
        $decoded = base64_decode(strtr($continuation . str_repeat('=', $padding), '-_', '+/'), true);
        try {
            $payload = $decoded === false ? null : json_decode($decoded, true, flags: JSON_THROW_ON_ERROR);
            if (!is_array($payload) || array_keys($payload) !== ['appId', 'policyId', 'evaluatedAt', 'providerCursor']) {
                throw new InvalidArgumentException('Invalid retention continuation.');
            }
            foreach (['appId', 'policyId', 'evaluatedAt', 'providerCursor'] as $field) {
                if (!is_string($payload[$field]) || $payload[$field] === '') throw new InvalidArgumentException('Invalid retention continuation.');
            }
            if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/', $payload['appId'])
                || !preg_match('/^[a-z][a-z0-9_-]{1,63}$/', $payload['policyId'])
                || strlen($payload['providerCursor']) > 2048) {
                throw new InvalidArgumentException('Invalid retention continuation.');
            }
            $payload['evaluatedAt'] = (new DateTimeImmutable($payload['evaluatedAt']))->format(DATE_ATOM);
            return $payload;
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (\Throwable) {
            throw new InvalidArgumentException('Invalid retention continuation.');
        }
    }
}
