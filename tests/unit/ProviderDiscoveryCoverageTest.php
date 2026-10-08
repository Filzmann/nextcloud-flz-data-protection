<?php

declare(strict_types=1);

use OCA\FlzDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCA\FlzDataProtection\Model\ProviderCoverageProfile;
use OCA\FlzDataProtection\PublicApi\V1\DataSubjectRef;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataPage;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataProvider;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FlzDataProtection\PublicApi\V1\ProviderDescriptor;
use OCA\FlzDataProtection\Service\PersonalDataAggregator;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;

$assertSame = static function (mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Erwartet: ' . var_export($expected, true) . '; erhalten: ' . var_export($actual, true));
    }
};

$provider = static function (string $appId, string $contractVersion = '1.0'): PersonalDataProvider {
    return new class($appId, $contractVersion) implements PersonalDataProvider {
        public function __construct(private string $appId, private string $contractVersion) {
        }

        public function descriptor(): ProviderDescriptor {
            return new ProviderDescriptor($this->appId, strtoupper($this->appId), $this->contractVersion, ['nextcloud-user'], ['personal-data'], 25);
        }

        public function collect(PersonalDataRequest $request): PersonalDataPage {
            return new PersonalDataPage('not_applicable');
        }
    };
};

$dispatches = 0;
$events = new class($provider, $dispatches) implements IEventDispatcher {
    /** @var Closure(string, string=): PersonalDataProvider */
    private Closure $provider;

    public function __construct(Closure $provider, private int &$dispatches) {
        $this->provider = $provider;
    }

    public function dispatchTyped(Event $event): Event {
        $this->dispatches++;
        if (!$event instanceof RegisterPersonalDataProvidersEvent) {
            throw new RuntimeException('Unexpected event type.');
        }

        $event->register(($this->provider)('available_app'));
        $event->register(($this->provider)('duplicate_app'));
        $event->register(($this->provider)('duplicate_app'));
        $event->register(($this->provider)('future_app', '2.0'));
        return $event;
    }
};

$request = new PersonalDataRequest(
    new DataSubjectRef('nextcloud-user', 'synthetic-user'),
    'de',
    'access-report',
    50,
    [],
);
$coverageProfile = new ProviderCoverageProfile(['available_app', 'duplicate_app', 'future_app', 'missing_app']);
$report = (new PersonalDataAggregator($events))->collect($request, $coverageProfile);

$assertSame(1, $dispatches, 'Die Providerregistrierung wurde nicht genau einmal je Auskunftslauf ausgelöst.');
$assertSame(['available_app', 'duplicate_app', 'future_app', 'missing_app'], array_keys($report->providers()), 'Coverage und Event-Snapshot wurden nicht deterministisch zusammengeführt.');
$assertSame('not_applicable', $report->providers()['available_app']->status(), 'Der kompatible Provider wurde nicht ausgeführt.');
$assertSame('missing', $report->providers()['duplicate_app']->status(), 'Eine mehrdeutige doppelte Providerregistrierung wurde ausgeführt.');
$assertSame('missing', $report->providers()['future_app']->status(), 'Ein inkompatibler erwarteter Provider wurde nicht sichtbar als fehlend markiert.');
$assertSame('missing', $report->providers()['missing_app']->status(), 'Ein erwarteter, aber nicht registrierter Provider fehlt im Bericht.');
$assertSame(false, $report->isCoverageComplete(), 'Unvollständige Coverage wurde als vollständig gemeldet.');

$unconfiguredEvents = new class($provider) implements IEventDispatcher {
    /** @var Closure(string, string=): PersonalDataProvider */
    private Closure $provider;

    public function __construct(Closure $provider) {
        $this->provider = $provider;
    }

    public function dispatchTyped(Event $event): Event {
        if (!$event instanceof RegisterPersonalDataProvidersEvent) {
            throw new RuntimeException('Unexpected event type.');
        }
        $event->register(($this->provider)('available_app'));
        return $event;
    }
};
$unconfiguredReport = (new PersonalDataAggregator($unconfiguredEvents))->collect($request);
$assertSame(true, $unconfiguredReport->isRegistrySnapshotComplete(), 'Ein vollständiger Registry-Snapshot wurde nicht erkannt.');
$assertSame(false, $unconfiguredReport->isCoverageComplete(), 'Ein Registry-Snapshot ohne Coverage-Profil wurde als vollständige Instanzabdeckung gemeldet.');

$failingDiscoveryEvents = new class($provider) implements IEventDispatcher {
    /** @var Closure(string, string=): PersonalDataProvider */
    private Closure $provider;

    public function __construct(Closure $provider) {
        $this->provider = $provider;
    }

    public function dispatchTyped(Event $event): Event {
        if (!$event instanceof RegisterPersonalDataProvidersEvent) {
            throw new RuntimeException('Unexpected event type.');
        }
        $event->register(($this->provider)('available_app'));
        throw new RuntimeException('synthetic discovery detail must not escape');
    }
};
$failedDiscoveryReport = (new PersonalDataAggregator($failingDiscoveryEvents))->collect(
    $request,
    new ProviderCoverageProfile(['available_app', 'missing_app']),
);
$assertSame('not_applicable', $failedDiscoveryReport->providers()['available_app']->status(), 'Ein vor dem Discoveryfehler registrierter Provider ging verloren.');
$assertSame('missing', $failedDiscoveryReport->providers()['missing_app']->status(), 'Ein Discoveryfehler hat eine erwartete Lücke verborgen.');
$assertSame('failed', $failedDiscoveryReport->discoveryStatus(), 'Der Discoveryfehler ist nicht datensparsam diagnostizierbar.');
$assertSame(false, $failedDiscoveryReport->isCoverageComplete(), 'Ein fehlgeschlagener Discoverylauf wurde als vollständig gemeldet.');

$invalidCoverageRejected = false;
try {
    new ProviderCoverageProfile(['valid_app', 'INVALID APP']);
} catch (InvalidArgumentException) {
    $invalidCoverageRejected = true;
}
$assertSame(true, $invalidCoverageRejected, 'Eine ungültige erwartete App-ID wurde akzeptiert.');

$emptyCoverageRejected = false;
try {
    new ProviderCoverageProfile([]);
} catch (InvalidArgumentException) {
    $emptyCoverageRejected = true;
}
$assertSame(true, $emptyCoverageRejected, 'Ein leeres Coverage-Profil wurde akzeptiert.');

echo "Provider discovery and coverage test passed.\n";
