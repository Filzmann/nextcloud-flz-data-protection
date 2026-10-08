<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Service;

use DomainException;
use OCA\FlzDataProtection\PublicApi\V1\RetentionPolicy;
use OCA\FlzDataProtection\PublicApi\V1\RetentionProvider;

final class RetentionProviderRegistry {
    public const CONTRACT_VERSION = '1.0';
    /** @var array<string, RetentionProvider> */
    private array $providers = [];

    public function register(RetentionProvider $provider): void {
        $descriptor = $provider->descriptor();
        $appId = $descriptor->appId();
        if ($descriptor->contractVersion() !== self::CONTRACT_VERSION || isset($this->providers[$appId])) {
            throw new DomainException('Incompatible retention provider.');
        }
        $policyIds = [];
        foreach ($provider->policies() as $policy) {
            if (!$policy instanceof RetentionPolicy || $policy->action() !== 'REVIEW' || isset($policyIds[$policy->policyId()])) {
                throw new DomainException('Invalid retention policy catalog.');
            }
            $policyIds[$policy->policyId()] = true;
        }
        if ($policyIds === []) throw new DomainException('Empty retention policy catalog.');
        $this->providers[$appId] = $provider;
    }

    /** @return array<string, RetentionProvider> */
    public function snapshot(): array { return $this->providers; }
}
