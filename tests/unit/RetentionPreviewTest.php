<?php

declare(strict_types=1);

use OCA\FlzDataProtection\PublicApi\V1\RegisterRetentionProvidersEvent;
use OCA\FlzDataProtection\PublicApi\V1\RetentionCandidate;
use OCA\FlzDataProtection\PublicApi\V1\RetentionPolicy;
use OCA\FlzDataProtection\PublicApi\V1\RetentionPreviewPage;
use OCA\FlzDataProtection\PublicApi\V1\RetentionPreviewRequest;
use OCA\FlzDataProtection\PublicApi\V1\RetentionProvider;
use OCA\FlzDataProtection\PublicApi\V1\RetentionProviderDescriptor;
use OCA\FlzDataProtection\Service\RetentionPreviewAggregator;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;

$assertSame = static function (mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Erwartet: ' . var_export($expected, true) . '; erhalten: ' . var_export($actual, true));
    }
};

$policy = new RetentionPolicy(
    'export_metadata_review',
    'Exportmetadaten',
    'Nachweis erzeugter Exporte',
    'CREATED_AT',
    180,
    'REVIEW',
    '1.0',
);
$calendarPolicy = new RetentionPolicy(
    'calendar_year_review',
    'Kalenderdaten',
    'Prüfung nach Kalenderfrist',
    'COMPLETED_AT',
    'P1Y',
    'REVIEW',
    '1.0',
);
if ($calendarPolicy->durationPeriod() !== 'P1Y' || isset($calendarPolicy->toArray()['durationDays']) || ($calendarPolicy->toArray()['durationPeriod'] ?? null) !== 'P1Y') {
    throw new RuntimeException('Kalenderbasierte Retention-Dauer wird nicht verlustfrei projiziert.');
}
$candidate = new RetentionCandidate(
    'export_metadata_review',
    'permission-matrix:export:17',
    '2026-01-01T10:00:00+00:00',
    'REVIEW',
    'Konfigurierte Prüffrist überschritten.',
    ['Datentyp' => 'Exportmetadaten'],
);

$secondCandidate = new RetentionCandidate(
    'export_metadata_review',
    'permission-matrix:export:18',
    '2026-01-02T10:00:00+00:00',
    'REVIEW',
    'Konfigurierte Prüffrist überschritten.',
);
$provider = new class($policy, $candidate, $secondCandidate) implements RetentionProvider {
    public function __construct(private RetentionPolicy $policy, private RetentionCandidate $candidate, private RetentionCandidate $secondCandidate) {}
    public function descriptor(): RetentionProviderDescriptor {
        return new RetentionProviderDescriptor('flz_permission_matrix', 'Berechtigungsmatrix', '1.0', 200);
    }
    public function policies(): array { return [$this->policy]; }
    public function preview(RetentionPreviewRequest $request): RetentionPreviewPage {
        if ($request->cursor() === null) {
            return new RetentionPreviewPage('partial', [$this->candidate], [], 'provider-page-2');
        }
        if ($request->cursor() !== 'provider-page-2') throw new InvalidArgumentException('Unexpected cursor.');
        return new RetentionPreviewPage('complete', [$this->secondCandidate]);
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
$assertSame(false, $report['coverageComplete'], 'Eine Registry ohne Sollprofil darf keine vollständige Coverage behaupten.');
$assertSame('partial', $report['providers']['flz_permission_matrix']['status'], 'Die erste Providerseite wurde nicht als partiell ausgewiesen.');
$assertSame('REVIEW', $report['providers']['flz_permission_matrix']['candidates'][0]['action'], 'Eine Vorschau darf keine destruktive Maßnahme behaupten.');
$continuation = $report['providers']['flz_permission_matrix']['continuations']['export_metadata_review'] ?? null;
if (!is_string($continuation) || $continuation === '' || str_contains($continuation, 'provider-page-2')) {
    throw new RuntimeException('Der opake Fortsetzungsvertrag fehlt oder legt den Provider-Cursor offen.');
}
$continued = (new RetentionPreviewAggregator($events))->collect('2099-01-01T00:00:00+00:00', 100, $continuation);
$assertSame('2026-08-23T12:00:00+00:00', $continued['evaluatedAt'], 'Eine Folgeseite hat den ursprünglichen Bewertungszeitpunkt verloren.');
$assertSame('permission-matrix:export:18', $continued['providers']['flz_permission_matrix']['candidates'][0]['reference'], 'Die explizite Folgeseite fehlt.');
$assertSame([], $continued['providers']['flz_permission_matrix']['continuations'], 'Eine vollständige Folgeseite bietet einen falschen Cursor an.');
$assertSame(false, method_exists($provider, 'execute'), 'Der V1-Preview-Vertrag darf keinen Ausführungspfad anbieten.');

$emptyEvents = new class implements IEventDispatcher {
    public function dispatchTyped(Event $event): Event { return $event; }
};
$emptyReport = (new RetentionPreviewAggregator($emptyEvents))->collect('2026-08-23T12:00:00+00:00', 100);
$assertSame([], $emptyReport['providers'], 'Eine fehlende Provider-App darf keine erfundene Coverage erzeugen.');
$assertSame(false, $emptyReport['coverageComplete'], 'Eine leere Registry darf keine vollständige Coverage behaupten.');

try {
    (new RetentionPreviewAggregator($events))->collect('2026-08-23T12:00:00+00:00', 100, 'manipulated');
    throw new RuntimeException('Ein manipulierter Fortsetzungs-Token wurde akzeptiert.');
} catch (InvalidArgumentException) {
}

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
$assertSame('partial', $partial['providers']['flz_permission_matrix']['status'], 'Ein Providerfehler hat einen intakten Provider verdeckt.');
$assertSame('failed', $partial['providers']['bad_provider']['status'], 'Ein Providerfehler wurde nicht isoliert sichtbar gemacht.');
$assertSame(false, str_contains(json_encode($partial, JSON_THROW_ON_ERROR), 'synthetic private detail'), 'Interne Providerdetails sind ausgetreten.');

$unstablePolicyProvider = new class($policy) implements RetentionProvider {
    private int $policyCalls = 0;

    public function __construct(private RetentionPolicy $policy) {}
    public function descriptor(): RetentionProviderDescriptor {
        return new RetentionProviderDescriptor('unstable_policy_provider', 'Instabiler Policy-Provider', '1.0', 20);
    }
    public function policies(): array {
        $this->policyCalls++;
        if ($this->policyCalls > 1) throw new RuntimeException('synthetic policy detail must not escape');
        return [$this->policy];
    }
    public function preview(RetentionPreviewRequest $request): RetentionPreviewPage {
        throw new RuntimeException('Preview must not run after the policy catalog failed.');
    }
};
$policyFailureEvents = new class($provider, $unstablePolicyProvider) implements IEventDispatcher {
    public function __construct(private RetentionProvider $good, private RetentionProvider $unstable) {}
    public function dispatchTyped(Event $event): Event {
        if (!$event instanceof RegisterRetentionProvidersEvent) throw new RuntimeException('Unexpected event.');
        $event->register($this->good);
        $event->register($this->unstable);
        return $event;
    }
};
$policyFailureReport = (new RetentionPreviewAggregator($policyFailureEvents))->collect('2026-08-23T12:00:00+00:00', 100);
$assertSame('partial', $policyFailureReport['providers']['flz_permission_matrix']['status'], 'Ein Policy-Katalogfehler hat einen intakten Provider verdeckt.');
$assertSame('failed', $policyFailureReport['providers']['unstable_policy_provider']['status'], 'Ein Policy-Katalogfehler wurde nicht appweise isoliert.');
$assertSame(false, str_contains(json_encode($policyFailureReport, JSON_THROW_ON_ERROR), 'synthetic policy detail'), 'Interne Policy-Fehlerdetails sind ausgetreten.');

$event = new RegisterRetentionProvidersEvent();
$event->register($provider);
$event->register($provider);
$assertSame([], $event->providers(), 'Eine doppelte Provider-ID muss fail-closed aus der Registry entfernt werden.');
$assertSame(['flz_permission_matrix' => 'Provider incompatible.'], $event->registrationFailures(), 'Der Registrierungsfehler ist nicht stabil sichtbar.');

$incompatibleProvider = new class($policy) implements RetentionProvider {
    public function __construct(private RetentionPolicy $policy) {}
    public function descriptor(): RetentionProviderDescriptor {
        return new RetentionProviderDescriptor('legacy_provider', 'Alter Provider', '2.0', 20);
    }
    public function policies(): array { return [$this->policy]; }
    public function preview(RetentionPreviewRequest $request): RetentionPreviewPage {
        throw new RuntimeException('Ein inkompatibler Provider darf nicht ausgeführt werden.');
    }
};
$incompatibleEvent = new RegisterRetentionProvidersEvent();
$incompatibleEvent->register($incompatibleProvider);
$assertSame([], $incompatibleEvent->providers(), 'Ein inkompatibler Provider wurde ausführbar registriert.');
$assertSame(['legacy_provider' => 'Provider incompatible.'], $incompatibleEvent->registrationFailures(), 'Die inkompatible Vertragsversion bleibt nicht sichtbar.');

$brokenDescriptor = new class implements RetentionProvider {
    public function descriptor(): RetentionProviderDescriptor { throw new RuntimeException('synthetic descriptor detail'); }
    public function policies(): array { throw new RuntimeException('must not run'); }
    public function preview(RetentionPreviewRequest $request): RetentionPreviewPage { throw new RuntimeException('must not run'); }
};
$descriptorEvent = new RegisterRetentionProvidersEvent();
$descriptorEvent->register($brokenDescriptor);
$assertSame([], $descriptorEvent->providers(), 'Ein Provider mit defektem Descriptor wurde registriert.');
$assertSame(['unknown_provider_1' => 'Provider incompatible.'], $descriptorEvent->registrationFailures(), 'Ein Descriptorfehler ist nicht isoliert diagnostizierbar.');

foreach ([
    static fn() => new RetentionPolicy('invalid id', 'Daten', 'Zweck', 'CREATED_AT', 180, 'REVIEW', '1.0'),
    static fn() => new RetentionPolicy('valid_id', 'Daten', 'Zweck', 'CREATED_AT', 0, 'REVIEW', '1.0'),
    static fn() => new RetentionCandidate('valid_id', 'ref', 'ungültig', 'DELETE', 'Grund'),
    static fn() => new RetentionPreviewRequest('valid_id', 'ungültig', 20),
] as $invalidFactory) {
    try {
        $invalidFactory();
        throw new RuntimeException('Ungültige Retention-Vertragsdaten wurden akzeptiert.');
    } catch (InvalidArgumentException) {
    }
}

echo "Retention preview tests passed.\n";
