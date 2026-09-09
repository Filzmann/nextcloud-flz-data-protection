<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Service;

use DomainException;
use OCA\FilzmannDataProtection\PublicApi\V1\ProcessingMetadataProvider;
use Throwable;

final class ProcessingMetadataProviderRegistry {
    public const CONTRACT_VERSION = '1.0';

    /** @var array<string, ProcessingMetadataProvider> */
    private array $providers = [];

    public function register(ProcessingMetadataProvider $provider): void {
        try {
            $descriptor = $provider->descriptor();
        } catch (Throwable) {
            throw new DomainException('Processing metadata provider unavailable.');
        }
        $appId = $descriptor->appId();
        if ($descriptor->contractVersion() !== self::CONTRACT_VERSION || isset($this->providers[$appId])) {
            throw new DomainException('Incompatible processing metadata provider.');
        }
        try {
            $catalog = $provider->catalog();
        } catch (Throwable) {
            throw new DomainException('Processing metadata catalog unavailable.');
        }
        if ($catalog->appId() !== $appId) {
            throw new DomainException('Processing metadata catalog belongs to another app.');
        }
        $this->providers[$appId] = $provider;
    }

    /** @return array<string, ProcessingMetadataProvider> */
    public function snapshot(): array { return $this->providers; }
}
