<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Listener;

use OCA\FlzDataProtection\PublicApi\V1\ScopeAuthorizationQueryEvent;
use OCA\FlzDataProtection\Service\RiskScopeAuthorizationService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

final class ScopeAuthorizationQueryListener implements IEventListener {
    public function __construct(private RiskScopeAuthorizationService $authorizations) {
    }

    public function handle(Event $event): void {
        if (!$event instanceof ScopeAuthorizationQueryEvent) {
            return;
        }

        $event->respond(
            $this->authorizations->isAuthorized(
                $event->consumerAppId(),
                $event->scopeId(),
                $event->requestedContractVersion(),
            ),
            ScopeAuthorizationQueryEvent::CONTRACT_VERSION,
        );
    }
}
