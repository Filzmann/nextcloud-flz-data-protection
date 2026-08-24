<?php

declare(strict_types=1);

use OCA\FilzmannDataProtection\PublicApi\V1\RegisterRetentionProvidersEvent;
use OCA\FilzmannDataProtection\PublicApi\V1\RetentionCandidate;
use OCA\FilzmannDataProtection\PublicApi\V1\RetentionPolicy;
use OCA\FilzmannDataProtection\PublicApi\V1\RetentionPreviewPage;
use OCA\FilzmannDataProtection\PublicApi\V1\RetentionPreviewRequest;
use OCA\FilzmannDataProtection\PublicApi\V1\RetentionProvider;
use OCA\FilzmannDataProtection\PublicApi\V1\RetentionProviderDescriptor;
use OCA\FilzmannDataProtection\Service\RetentionPreviewAggregator;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;

$assertSame = static function (mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Erwartet: ' . var_export($expected, true) . '; erhalten: ' . var_export($actual, true));
    }
};

$policy = new RetentionPolicy(
    'export-metadata-review',
    'Exportmetadaten',
    'Nachweis erzeugter Exporte',
    'CREATED_AT',
    180,
    'REVIEW',
    '1.0',
);
$candidate = new RetentionCandidate(
    'export-metadata-review',
    'permission-matrix:export:17',
    '2026-01-01T10:00:00+00:00',
    'REVIEW',
    'Konfigurierte Prüffrist überschritten.',
    ['Datentyp' => 'Exportmetadaten'],
);

$provider = new class($policy, $candidate) implements RetentionProvider {
    public function __construct(private RetentionPolicy $policy, private RetentionCandidate $candidate) {}
    public function descriptor(): RetentionProviderDescriptor {
        return new RetentionProviderDescriptor('filzmann_permission_matrix', 'Berechtigungsmatrix', '1.0', 200);
    }
    public function policies(): array { return [$this->policy]; }
    public function preview(RetentionPreviewRequest $request): RetentionPreviewPage {
        return new RetentionPreviewPage('complete', [$this->candidate]);
    }
};

$dispatches = 0;
$events = new class($provider, $dispatches) implements IEventDispatcher {
    public function __construct(private RetentionProvider $provider, private int &$dispatches) {}
    public function dispatchTyped(Event $event): Event {
        $this->dispatches++;
        if (!$event instanceof RegisterRetentionProvidersEvent) throw new RuntimeException('Unexpected event.');
        $event->register($this->provider);
        return $event;
    }
};

$report = (new RetentionPreviewAggregator($events))->collect('2026-08-23T12:00:00+00:00', 100);
$assertSame(1, $dispatches, 'Retention-Provider müssen genau einmal je Vorschau entdeckt werden.');
$assertSame('complete', $report['discoveryStatus'], 'Eine erfolgreiche Discovery wurde nicht als vollständig gemeldet.');
$assertSame('complete', $report['providers']['filzmann_permission_matrix']['status'], 'Der kompatible Provider wurde nicht ausgeführt.');
$assertSame('REVIEW', $report['providers']['filzmann_permission_matrix']['candidates'][0]['action'], 'Eine Vorschau darf keine destruktive Maßnahme behaupten.');
$assertSame(false, method_exists($provider, 'execute'), 'Der V1-Preview-Vertrag darf keinen Ausführungspfad anbieten.');

$badProvider = new class($policy) implements RetentionProvider {
    public function __construct(private RetentionPolicy $policy) {}
    public function descriptor(): RetentionProviderDescriptor {
        return new RetentionProviderDescriptor('bad_provider', 'Defekter Provider', '1.0', 20);
    }
    public function policies(): array { return [$this->policy]; }
    public function preview(RetentionPreviewRequest $request): RetentionPreviewPage {
        throw new RuntimeException('synthetic private detail');
    }
};
$failingEvents = new class($provider, $badProvider) implements IEventDispatcher {
    public function __construct(private RetentionProvider $good, private RetentionProvider $bad) {}
    public function dispatchTyped(Event $event): Event {
        if (!$event instanceof RegisterRetentionProvidersEvent) throw new RuntimeException('Unexpected event.');
        $event->register($this->good);
        $event->register($this->bad);
        return $event;
    }
};
$partial = (new RetentionPreviewAggregator($failingEvents))->collect('2026-08-23T12:00:00+00:00', 100);
$assertSame('complete', $partial['providers']['filzmann_permission_matrix']['status'], 'Ein Providerfehler hat einen intakten Provider verdeckt.');
$assertSame('failed', $partial['providers']['bad_provider']['status'], 'Ein Providerfehler wurde nicht isoliert sichtbar gemacht.');
$assertSame(false, str_contains(json_encode($partial, JSON_THROW_ON_ERROR), 'synthetic private detail'), 'Interne Providerdetails sind ausgetreten.');

$event = new RegisterRetentionProvidersEvent();
$event->register($provider);
$event->register($provider);
$assertSame([], $event->providers(), 'Eine doppelte Provider-ID muss fail-closed aus der Registry entfernt werden.');
$assertSame(['filzmann_permission_matrix' => 'Provider incompatible.'], $event->registrationFailures(), 'Der Registrierungsfehler ist nicht stabil sichtbar.');

foreach ([
    static fn() => new RetentionPolicy('invalid id', 'Daten', 'Zweck', 'CREATED_AT', 180, 'REVIEW', '1.0'),
    static fn() => new RetentionPolicy('valid-id', 'Daten', 'Zweck', 'CREATED_AT', 0, 'REVIEW', '1.0'),
    static fn() => new RetentionCandidate('valid-id', 'ref', 'ungültig', 'DELETE', 'Grund'),
    static fn() => new RetentionPreviewRequest('valid-id', 'ungültig', 20),
] as $invalidFactory) {
    try {
        $invalidFactory();
        throw new RuntimeException('Ungültige Retention-Vertragsdaten wurden akzeptiert.');
    } catch (InvalidArgumentException) {
    }
}

echo "Retention preview tests passed.\n";
