<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Service;

use DomainException;
use OCA\FlzDataProtection\PublicApi\V1\ProcessingMetadataProvider;
use Throwable;

final class ProcessingMetadataProviderRegistry {
    public const CONTRACT_VERSION = '1.0';

    /** @var array<string, ProcessingMetadataProvider> */
    private array $providers = [];

    public function register(ProcessingMetadataProvider $provider): void {
        try {
            $descriptor = $provider->descriptor();
        } catch (Throwable) {
            throw new DomainException('Verarbeitungsmetadaten-Provider ist nicht verfügbar.');
        }
        $appId = $descriptor->appId();
        if ($descriptor->contractVersion() !== self::CONTRACT_VERSION || isset($this->providers[$appId])) {
            throw new DomainException('Inkompatibler Verarbeitungsmetadaten-Provider.');
        }
        try {
            $catalog = $provider->catalog();
        } catch (Throwable) {
            throw new DomainException('Der Verarbeitungsmetadaten-Katalog ist nicht verfügbar.');
        }
        if ($catalog->appId() !== $appId) {
            throw new DomainException('Der Verarbeitungsmetadaten-Katalog gehört zu einer anderen App.');
        }
        $this->providers[$appId] = $provider;
    }

    /** @return array<string, ProcessingMetadataProvider> */
    public function snapshot(): array { return $this->providers; }
}
