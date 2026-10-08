<?php

declare(strict_types=1);

use OCA\FlzDataProtection\Db\RiskScopeAuthorizationRepositoryInterface;
use OCA\FlzDataProtection\PublicApi\V1\ScopeAuthorizationQueryEvent;
use OCA\FlzDataProtection\Listener\ScopeAuthorizationQueryListener;
use OCA\FlzDataProtection\Service\RiskScopeAuthorizationService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;

$assertSame = static function (mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
};

$repository = new class implements RiskScopeAuthorizationRepositoryInterface {
    public array $rows = [];
    public function latest(string $scopeId): ?array {
        $matching = array_values(array_filter($this->rows, static fn(array $row): bool => $row['scopeId'] === $scopeId));
        return $matching === [] ? null : $matching[array_key_last($matching)];
    }
    public function history(string $scopeId): array {
        return array_values(array_filter($this->rows, static fn(array $row): bool => $row['scopeId'] === $scopeId));
    }
    public function appendIfCurrent(array $configuration, int $expectedRevision): array {
        $actualRevision = (int)($this->latest($configuration['scopeId'])['revision'] ?? 0);
        if ($actualRevision !== $expectedRevision) {
            throw new DomainException('Die Scope-Konfiguration wurde zwischenzeitlich geändert.');
        }
        $configuration['revision'] = $expectedRevision + 1;
        $this->rows[] = $configuration;
        return $configuration;
    }
};
$groups = new class implements IGroupManager {
    public function isAdmin(string $uid): bool { return false; }
    public function isInGroup(string $uid, string $gid): bool {
        return $uid === 'privacy-reviewer' && $gid === RiskScopeAuthorizationService::CONFIGURATOR_GROUP;
    }
    public function groupExists(string $gid): bool { return $gid === RiskScopeAuthorizationService::CONFIGURATOR_GROUP; }
};
$session = new class implements IUserSession {
    public string $uid = 'privacy-reviewer';
    public function getUser(): ?IUser {
        return new class($this->uid) implements IUser {
            public function __construct(private string $uid) {}
            public function getUID(): string { return $this->uid; }
        };
    }
};
$clock = new class implements ITimeFactory {
    public DateTimeImmutable $value;
    public function __construct() { $this->value = new DateTimeImmutable('2026-10-01T10:00:00+00:00'); }
    public function now(): DateTimeImmutable { return $this->value; }
};

$service = new RiskScopeAuthorizationService($repository, $groups, $session, $clock);
$scope = ScopeAuthorizationQueryEvent::FLZROOM_SECRETARIAT_FOREIGN_BOOKING_INTERVENTION;
$version = ScopeAuthorizationQueryEvent::CONTRACT_VERSION;

$empty = $service->status();
$assertSame(true, $empty['performanceMonitoringProhibited'], 'Das Kontrollverbot muss eine unveränderliche Produktgrenze sein.');
$assertSame(false, $empty['scopes'][$scope]['authorized'], 'Ein unkonfigurierter Scope muss gesperrt starten.');
$assertSame('configuration_missing', $empty['scopes'][$scope]['status'], 'Der fehlende Instanzstand muss neutral diagnostizierbar sein.');
$assertSame(false, $service->isAuthorized('flzroom', $scope, $version), 'Ein fehlender Scope wurde freigegeben.');

$configuration = [
    'scopeId' => $scope,
    'enabled' => true,
    'policyRevision' => 'SYNTH-POLICY-1',
    'authorizationReference' => 'SYNTH-AUTHORIZATION-1',
    'effectiveAt' => '2026-10-01T09:00:00+00:00',
    'expiresAt' => '2027-10-01T09:00:00+00:00',
    'dpoConfirmed' => true,
    'performanceMonitoringProhibited' => true,
    'expectedRevision' => 0,
];

$beforeDeniedSave = $repository->rows;
$session->uid = 'ordinary-user';
try {
    $service->save($configuration);
    throw new RuntimeException('Eine unberechtigte Person konnte einen Risikoscope konfigurieren.');
} catch (DomainException) {
}
$assertSame($beforeDeniedSave, $repository->rows, 'Eine verweigerte Konfiguration hatte Nebenwirkungen.');

$session->uid = 'privacy-reviewer';
$saved = $service->save($configuration);
$assertSame(1, $saved['scopes'][$scope]['configuration']['revision'], 'Die erste Scope-Konfiguration muss Revision 1 erhalten.');
$assertSame(true, $saved['scopes'][$scope]['authorized'], 'Eine wirksame DPO-bestätigte Konfiguration wurde nicht freigegeben.');
$assertSame(true, $service->isAuthorized('flzroom', $scope, $version), 'Der autorisierte Pilot-Consumer wurde abgewiesen.');
$assertSame(false, $service->isAuthorized('other_app', $scope, $version), 'Ein fremder Consumer konnte den flzroom-Scope verwenden.');
$assertSame(false, $service->isAuthorized('flzroom', 'unknown.scope', $version), 'Ein unbekannter Scope wurde freigegeben.');
$assertSame(false, $service->isAuthorized('flzroom', $scope, '2.0'), 'Eine inkompatible Vertragsversion wurde freigegeben.');

$event = new ScopeAuthorizationQueryEvent('flzroom', $scope, $version);
(new ScopeAuthorizationQueryListener($service))->handle($event);
$assertSame('authorized', $event->status(), 'Der öffentliche Providervertrag liefert keinen neutralen Allow-Status.');
$assertSame(true, $event->isAuthorized(), 'Der öffentliche Providervertrag gibt den wirksamen Scope nicht frei.');
foreach (['policyRevision', 'authorizationReference', 'effectiveAt', 'expiresAt', 'dpoConfirmed'] as $privateDetail) {
    if (method_exists($event, $privateDetail)) {
        throw new RuntimeException('Der öffentliche Vertrag legt kundenlokale Policydetails offen: ' . $privateDetail);
    }
}

$incompatible = new ScopeAuthorizationQueryEvent('flzroom', $scope, '2.0');
(new ScopeAuthorizationQueryListener($service))->handle($incompatible);
$assertSame('incompatible', $incompatible->status(), 'Eine inkompatible Vertragsversion bleibt nicht explizit gesperrt.');
$assertSame(false, $incompatible->isAuthorized(), 'Eine inkompatible Vertragsversion wurde freigegeben.');

$clock->value = new DateTimeImmutable('2027-10-01T09:00:00+00:00');
$assertSame(false, $service->isAuthorized('flzroom', $scope, $version), 'Der Scope bleibt am Ablaufzeitpunkt aktiv.');
$assertSame('expired', $service->status()['scopes'][$scope]['status'], 'Ein abgelaufener Scope ist nicht neutral diagnostizierbar.');
$clock->value = new DateTimeImmutable('2026-10-01T10:00:00+00:00');

$beforeInvalid = $repository->rows;
foreach ([
    [...$configuration, 'expectedRevision' => 0],
    [...$configuration, 'expectedRevision' => 1, 'dpoConfirmed' => false],
    [...$configuration, 'expectedRevision' => 1, 'performanceMonitoringProhibited' => false],
    [...$configuration, 'expectedRevision' => 1, 'expiresAt' => '2026-10-01T08:00:00+00:00'],
    [...$configuration, 'expectedRevision' => 1, 'authorizationReference' => 'Named person Example'],
] as $invalid) {
    try {
        $service->save($invalid);
        throw new RuntimeException('Eine ungültige Scope-Konfiguration wurde akzeptiert.');
    } catch (DomainException|InvalidArgumentException) {
    }
    $assertSame($beforeInvalid, $repository->rows, 'Eine ungültige Scope-Konfiguration hatte Nebenwirkungen.');
}

$disabled = $service->save([...$configuration, 'enabled' => false, 'expectedRevision' => 1]);
$assertSame(false, $disabled['scopes'][$scope]['authorized'], 'Ein deaktivierter Scope wurde freigegeben.');
$assertSame('disabled', $disabled['scopes'][$scope]['status'], 'Eine Deaktivierung ist nicht neutral diagnostizierbar.');
$assertSame(false, $service->isAuthorized('flzroom', $scope, $version), 'Ein deaktivierter Scope wurde vom Provider freigegeben.');

$stored = $repository->rows;
$repository->rows[1]['authorizationReference'] = '';
$assertSame(false, $service->isAuthorized('flzroom', $scope, $version), 'Ein beschädigter persistierter Stand wurde freigegeben.');
$assertSame('configuration_invalid', $service->status()['scopes'][$scope]['status'], 'Ein beschädigter Stand ist nicht neutral diagnostizierbar.');
$repository->rows = $stored;
$repository->rows[1]['schemaVersion'] = '9.0';
$corruptedDocument = $repository->rows;
$assertSame(false, $service->isAuthorized('flzroom', $scope, $version), 'Ein inkompatibles persistiertes Dokumentschema wurde freigegeben.');
try {
    $service->save([...$configuration, 'expectedRevision' => 2]);
    throw new RuntimeException('Ein inkompatibles persistiertes Dokumentschema wurde überschrieben.');
} catch (DomainException|InvalidArgumentException) {
}
$assertSame($corruptedDocument, $repository->rows, 'Die abgewiesene Reparatur eines inkompatiblen Dokumentschemas hatte Nebenwirkungen.');

echo "Risk scope authorization provider contract passed.\n";
