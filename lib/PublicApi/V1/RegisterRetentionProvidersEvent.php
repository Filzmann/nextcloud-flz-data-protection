<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\PublicApi\V1;

use DomainException;
use OCA\FilzmannDataProtection\Service\RetentionProviderRegistry;
use OCP\EventDispatcher\Event;

final class RegisterRetentionProvidersEvent extends Event {
    private RetentionProviderRegistry $registry;
    private array $registrationFailures = [];

    public function __construct() {
        parent::__construct();
        $this->registry = new RetentionProviderRegistry();
    }

    public function register(RetentionProvider $provider): void {
        $appId = $provider->descriptor()->appId();
        try {
            $this->registry->register($provider);
        } catch (DomainException) {
            $this->registrationFailures[$appId] = 'Provider incompatible.';
        }
    }

    public function providers(): array {
        return array_diff_key($this->registry->snapshot(), $this->registrationFailures);
    }

    public function registrationFailures(): array { return $this->registrationFailures; }
}
