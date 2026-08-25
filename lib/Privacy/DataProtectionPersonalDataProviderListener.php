<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Privacy;

use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

final class DataProtectionPersonalDataProviderListener implements IEventListener {
    public function __construct(private DataProtectionPersonalDataProvider $provider) {
    }

    public function handle(Event $event): void {
        if ($event instanceof RegisterPersonalDataProvidersEvent) {
            $event->register($this->provider);
        }
    }
}
