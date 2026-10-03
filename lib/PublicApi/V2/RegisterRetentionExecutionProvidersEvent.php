<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\PublicApi\V2;

use OCP\EventDispatcher\Event;
use Throwable;

final class RegisterRetentionExecutionProvidersEvent extends Event {
    /** @var array<string, RetentionExecutionProvider> */
    private array $providers = [];
    /** @var array<string, string> */
    private array $failures = [];

    public function register(RetentionExecutionProvider $provider): void {
        $fallback = 'unknown_provider_' . (count($this->providers) + count($this->failures) + 1);
        try {
            $descriptor = $provider->descriptor();
            $appId = $descriptor->appId();
            if ($descriptor->contractVersion() !== '2.0' || isset($this->providers[$appId]) || isset($this->failures[$appId])) throw new \DomainException();
            $ids = [];
            foreach ($provider->policies() as $policy) {
                if (!$policy instanceof RetentionExecutionPolicy || $policy->action() !== 'DELETE' || isset($ids[$policy->policyId()])) throw new \DomainException();
                $ids[$policy->policyId()] = true;
            }
            if ($ids === []) throw new \DomainException();
            $this->providers[$appId] = $provider;
        } catch (Throwable) {
            $appId = isset($appId) && is_string($appId) ? $appId : $fallback;
            unset($this->providers[$appId]);
            $this->failures[$appId] = 'Provider incompatible.';
        }
    }
    public function providers(): array { return $this->providers; }
    public function registrationFailures(): array { return $this->failures; }
}
