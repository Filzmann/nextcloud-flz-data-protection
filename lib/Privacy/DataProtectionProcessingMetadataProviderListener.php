<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Privacy;

use OCA\FilzmannDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

final class DataProtectionProcessingMetadataProviderListener implements IEventListener {
    public function __construct(private DataProtectionProcessingMetadataProvider $provider) {}

    public function handle(Event $event): void {
        if ($event instanceof RegisterProcessingMetadataProvidersEvent) {
            $event->register($this->provider);
        }
    }
}
