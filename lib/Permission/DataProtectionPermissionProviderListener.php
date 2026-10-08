<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Permission;

use OCA\FlzPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

final class DataProtectionPermissionProviderListener implements IEventListener {
    public function __construct(private DataProtectionPermissionProvider $provider) {
    }

    public function handle(Event $event): void {
        if ($event instanceof RegisterPermissionProvidersEvent) {
            $event->register($this->provider);
        }
    }
}
