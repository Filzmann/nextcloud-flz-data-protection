<?php

declare(strict_types=1);

use OCA\FilzmannDataProtection\Db\RiskScopeAuthorizationRepositoryInterface;
use OCA\FilzmannDataProtection\PublicApi\V1\ScopeAuthorizationQueryEvent;
use OCA\FilzmannDataProtection\Service\RiskScopeAuthorizationService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;

$repository = new class implements RiskScopeAuthorizationRepositoryInterface {
    public array $rows = [];
    public bool $injectConcurrentWinner = false;
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
        if ($this->injectConcurrentWinner) {
            $this->injectConcurrentWinner = false;
            $this->rows[] = [
                ...$configuration,
                'revision' => $expectedRevision + 1,
                'enabled' => false,
                'policyRevision' => 'SYNTH-CONCURRENT-WINNER',
                'authorizationReference' => 'SYNTH-CONCURRENT-AUTH',
            ];
            throw new DomainException('Die Scope-Konfiguration wurde zwischenzeitlich geändert.');
        }
        $configuration['revision'] = $expectedRevision + 1;
        $this->rows[] = $configuration;
        return $configuration;
    }
};
$groups = new class implements IGroupManager {
    public function isAdmin(string $uid): bool { return false; }
    public function isInGroup(string $uid, string $gid): bool { return $uid === 'privacy-reviewer' && $gid === RiskScopeAuthorizationService::CONFIGURATOR_GROUP; }
    public function groupExists(string $gid): bool { return true; }
};
$session = new class implements IUserSession {
    public function getUser(): ?IUser { return new class implements IUser { public function getUID(): string { return 'privacy-reviewer'; } }; }
};
$clock = new class implements ITimeFactory {
    public function now(): DateTimeImmutable { return new DateTimeImmutable('2026-10-01T10:00:00+00:00'); }
};
$service = new RiskScopeAuthorizationService($repository, $groups, $session, $clock);
$scope = ScopeAuthorizationQueryEvent::ADROOM_SECRETARIAT_FOREIGN_BOOKING_INTERVENTION;
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
$service->save($configuration);
$repository->injectConcurrentWinner = true;

try {
    $service->save([...$configuration, 'policyRevision' => 'SYNTH-LOSER', 'expectedRevision' => 1]);
    throw new RuntimeException('Ein konkurrierender DPO-Schreibvorgang wurde nicht als Konflikt abgewiesen.');
} catch (DomainException) {
}
if (count($repository->rows) !== 2
    || $repository->rows[1]['policyRevision'] !== 'SYNTH-CONCURRENT-WINNER'
    || $repository->rows[1]['revision'] !== 2) {
    throw new RuntimeException('Der abgewiesene Schreibvorgang hat die konkurrierende Gewinnerrevision überschrieben.');
}
if ($service->status()['scopes'][$scope]['status'] !== 'disabled') {
    throw new RuntimeException('Nach dem Konflikt wird nicht die tatsächlich gespeicherte Gewinnerrevision projiziert.');
}

echo "Risk scope authorization concurrency contract passed.\n";
