<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\PublicApi\V1;

use OCA\FlzDataProtection\Service\ProcessingMetadataProviderRegistry;
use OCP\EventDispatcher\Event;
use Throwable;

final class RegisterProcessingMetadataProvidersEvent extends Event {
    private ProcessingMetadataProviderRegistry $registry;

    /** @var array<string, string> */
    private array $registrationFailures = [];

    public function __construct() {
        parent::__construct();
        $this->registry = new ProcessingMetadataProviderRegistry();
    }

    public function register(ProcessingMetadataProvider $provider): void {
        try {
            $appId = $provider->descriptor()->appId();
            $this->registry->register($provider);
        } catch (Throwable) {
            $appId ??= 'unknown_provider_' . (count($this->registrationFailures) + 1);
            $this->registrationFailures[$appId] = 'Processing metadata provider incompatible.';
        }
    }

    /** @return array<string, ProcessingMetadataProvider> */
    public function providers(): array {
        return array_diff_key($this->registry->snapshot(), $this->registrationFailures);
    }

    /** @return array<string, string> */
    public function registrationFailures(): array { return $this->registrationFailures; }
}
