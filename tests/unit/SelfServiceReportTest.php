<?php

declare(strict_types=1);

use OCA\FilzmannDataProtection\Controller\SelfServiceController;
use OCA\FilzmannDataProtection\Exception\AuthenticationRequiredException;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataPage;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataProvider;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FilzmannDataProtection\PublicApi\V1\ProviderDescriptor;
use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCA\FilzmannDataProtection\Service\PersonalDataAggregator;
use OCA\FilzmannDataProtection\Service\SelfServiceReportService;
use OCP\AppFramework\Http\Http;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserSession;

$assertSame = static function (mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Erwartet: ' . var_export($expected, true) . '; erhalten: ' . var_export($actual, true));
    }
};

$observedSubject = null;
$observedLanguage = null;
$provider = new class($observedSubject, $observedLanguage) implements PersonalDataProvider {
    public function __construct(private ?string &$observedSubject, private ?string &$observedLanguage) {
    }

    public function descriptor(): ProviderDescriptor {
        return new ProviderDescriptor('reference_app', 'Reference app', '1.0', ['nextcloud-user'], ['personal-data'], 25);
    }

    public function collect(PersonalDataRequest $request): PersonalDataPage {
        $this->observedSubject = $request->subject()->subjectId();
        $this->observedLanguage = $request->language();
        return new PersonalDataPage('not_applicable');
    }
};
$dispatchCount = 0;
$events = new class($provider, $dispatchCount) implements IEventDispatcher {
    public function __construct(private PersonalDataProvider $provider, private int &$dispatchCount) {
    }

    public function dispatchTyped(Event $event): Event {
        $this->dispatchCount++;
        if (!$event instanceof RegisterPersonalDataProvidersEvent) {
            throw new RuntimeException('Unexpected event type.');
        }
        $event->register($this->provider);
        return $event;
    }
};
$user = new class implements IUser {
    public function getUID(): string {
        return 'session-user';
    }
};
$session = new class($user) implements IUserSession {
    public function __construct(private ?IUser $user) {
    }

    public function getUser(): ?IUser {
        return $this->user;
    }
};
$l10n = new class implements IL10N {
    public function getLanguageCode(): string {
        return 'en';
    }
};
$service = new SelfServiceReportService($session, $l10n, new PersonalDataAggregator($events));
$report = $service->report();

$assertSame('session-user', $report['subject']['id'] ?? null, 'Die Auskunft ist nicht an die Sitzungs-UID gebunden.');
$assertSame('session-user', $observedSubject, 'Der Provider erhielt nicht die Sitzungs-UID.');
$assertSame('en', $observedLanguage, 'Der Provider erhielt nicht die native Nextcloud-Sprache.');
$assertSame(0, (new ReflectionMethod($service, 'report'))->getNumberOfParameters(), 'Der Self-Service akzeptiert eine frei übermittelte Zielperson.');
$assertSame(false, $report['coverageComplete'] ?? null, 'Ohne Coverage-Profil wurde eine vollständige Instanzauskunft behauptet.');

$request = new class implements IRequest {
};
$authorizedController = new SelfServiceController($request, $service);
$authorizedResponse = $authorizedController->report();
$assertSame(Http::STATUS_OK, $authorizedResponse->getStatus(), 'Der authentifizierte API-Aufruf wurde nicht erlaubt.');
$assertSame('session-user', $authorizedResponse->getData()['subject']['id'] ?? null, 'Der API-Aufruf gibt nicht die Sitzungs-UID aus.');

$anonymousSession = new class implements IUserSession {
    public function getUser(): ?IUser {
        return null;
    }
};
$anonymousDispatches = 0;
$anonymousEvents = new class($anonymousDispatches) implements IEventDispatcher {
    public function __construct(private int &$dispatches) {
    }

    public function dispatchTyped(Event $event): Event {
        $this->dispatches++;
        return $event;
    }
};
$anonymousService = new SelfServiceReportService($anonymousSession, $l10n, new PersonalDataAggregator($anonymousEvents));
$anonymousRejected = false;
try {
    $anonymousService->report();
} catch (AuthenticationRequiredException) {
    $anonymousRejected = true;
}
$assertSame(true, $anonymousRejected, 'Eine anonyme Self-Service-Auskunft wurde nicht verweigert.');
$assertSame(0, $anonymousDispatches, 'Eine verweigerte Auskunft hat Provider aufgerufen.');

$controller = new SelfServiceController($request, $anonymousService);
$response = $controller->report();
$assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus(), 'Der anonyme API-Aufruf liefert keinen 401-Status.');
$assertSame(['message' => 'Authentifizierung erforderlich.'], $response->getData(), 'Der anonyme API-Aufruf gibt einen unstabilen oder zu detaillierten Fehler aus.');
$assertSame(0, (new ReflectionMethod($controller, 'report'))->getNumberOfParameters(), 'Der API-Endpunkt akzeptiert eine frei übermittelte Zielperson.');

echo "Self-service report test passed.\n";
