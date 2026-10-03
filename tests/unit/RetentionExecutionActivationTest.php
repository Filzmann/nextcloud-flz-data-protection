<?php

declare(strict_types=1);

use OCA\FilzmannDataProtection\Db\RetentionExecutionActivationRepositoryInterface;
use OCA\FilzmannDataProtection\Controller\RetentionExecutionActivationController;
use OCA\FilzmannDataProtection\Service\RetentionExecutionActivationService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\IRequest;

$assertSame = static function (mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
};

$repository = new class implements RetentionExecutionActivationRepositoryInterface {
    public array $rows = [];
    public bool $injectConcurrentWinner = false;

    public function latest(): ?array {
        return $this->rows === [] ? null : $this->rows[array_key_last($this->rows)];
    }

    public function history(): array {
        return $this->rows;
    }

    public function historyForUid(string $uid, int $limit, int $offset, DateTimeImmutable $asOf): array {
        return array_slice(array_values(array_filter($this->rows, static fn(array $row): bool => $row['changedBy'] === $uid && $row['createdAt'] <= $asOf)), $offset, $limit);
    }

    public function appendIfCurrent(array $configuration, int $expectedRevision): array {
        $actualRevision = (int)($this->latest()['revision'] ?? 0);
        if ($actualRevision !== $expectedRevision) {
            throw new DomainException('Die technische Aktivierung wurde zwischenzeitlich geändert.');
        }
        if ($this->injectConcurrentWinner) {
            $this->injectConcurrentWinner = false;
            $this->rows[] = [
                ...$configuration,
                'revision' => $expectedRevision + 1,
                'enabled' => false,
            ];
            throw new DomainException('Die technische Aktivierung wurde zwischenzeitlich geändert.');
        }
        $configuration['revision'] = $expectedRevision + 1;
        $this->rows[] = $configuration;
        return $configuration;
    }
};
$groups = new class implements IGroupManager {
    public function isAdmin(string $uid): bool { return $uid === 'operator'; }
    public function isInGroup(string $uid, string $gid): bool { return false; }
    public function groupExists(string $gid): bool { return true; }
};
$session = new class implements IUserSession {
    public string $uid = 'operator';
    public function getUser(): ?IUser {
        return new class($this->uid) implements IUser {
            public function __construct(private string $uid) {}
            public function getUID(): string { return $this->uid; }
        };
    }
};
$clock = new class implements ITimeFactory {
    public DateTimeImmutable $value;
    public function __construct() { $this->value = new DateTimeImmutable('2026-10-02T10:00:00+00:00'); }
    public function now(): DateTimeImmutable { return $this->value; }
};

$service = new RetentionExecutionActivationService($repository, $groups, $session, $clock);
$controller = new RetentionExecutionActivationController(new class implements IRequest {}, $service);
$recommended = [
    'adroom:room_booking_delete',
    'adroom:temporary_admin_access_history_delete',
    'filzmann_data_protection:temporary_admin_access_history_delete',
];

$initial = $service->status();
$assertSame(false, $initial['executionAvailable'], 'Eine frische Installation muss standardmäßig deaktiviert sein.');
$assertSame('REVIEW', $initial['action'], 'Ohne technische Aktivierung darf keine Löschung laufen.');
$assertSame(['activation_missing'], $initial['blockers'], 'Der sichere Initialzustand muss eindeutig diagnostizierbar sein.');
$assertSame($recommended, $initial['recommendedPolicyIds'], 'Die freigegebenen Pilot-Policies müssen als Produktvorschlag sichtbar sein.');
$assertSame(true, $service->canConfigure(), 'Ein nativer Nextcloud-Admin muss die technische Aktivierung konfigurieren können.');
$assertSame(200, $controller->show()->getStatus(), 'Der native Operator kann den technischen Status nicht lesen.');

$activation = [
    'enabled' => true,
    'backupRegularDays' => 365,
    'backupBufferDays' => 5,
    'backupVerifiedAt' => '2026-10-01T09:00:00+00:00',
    'restoreVerifiedAt' => '2026-10-01T11:00:00+00:00',
    'verificationDueAt' => '2027-10-01T00:00:00+00:00',
    'expectedRevision' => 0,
];
$saved = $service->save($activation);
$assertSame('DELETE', $saved['action'], 'Die rein technische Aktivierung muss DELETE ohne Rechts- oder DPO-Felder freigeben.');
$assertSame(true, $saved['executionAvailable'], 'Die technisch gesunde Aktivierung bleibt fälschlich gesperrt.');
$assertSame($recommended, $saved['configuration']['approvedPolicyIds'], 'Die Aktivierung muss genau die empfohlenen Pilot-Policies freigeben.');
$assertSame('operator', $saved['configuration']['changedBy'], 'Der technische Operator muss serverseitig auditiert werden.');
foreach (['profileId', 'legalEvidenceReference', 'scopeReference', 'purposeReference', 'dpoConfirmed'] as $forbiddenField) {
    if (array_key_exists($forbiddenField, $saved['configuration'])) {
        throw new RuntimeException('Kundenlokales Rechts-/DPO-Feld blieb Ausführungsvoraussetzung: ' . $forbiddenField);
    }
}

$beforeDenied = $repository->rows;
$session->uid = 'dpo-only';
$assertSame(403, $controller->show()->getStatus(), 'Ein Nicht-Admin konnte den technischen Status lesen.');
$assertSame(403, $controller->save([...$activation, 'expectedRevision'=>1])->getStatus(), 'Ein Nicht-Admin konnte die technische Aktivierung schreiben.');
try {
    $service->save([...$activation, 'expectedRevision' => 1]);
    throw new RuntimeException('Ein Nicht-Admin konnte die technische Aktivierung ändern.');
} catch (DomainException) {
}
$assertSame($beforeDenied, $repository->rows, 'Eine verweigerte Aktivierung hatte Nebenwirkungen.');
$session->uid = 'operator';

foreach ([
    [...$activation, 'backupRegularDays' => 366, 'expectedRevision' => 1],
    [...$activation, 'backupBufferDays' => 6, 'expectedRevision' => 1],
    [...$activation, 'backupVerifiedAt' => '2026-10-03T00:00:00+00:00', 'expectedRevision' => 1],
    [...$activation, 'restoreVerifiedAt' => '2026-10-03T00:00:00+00:00', 'expectedRevision' => 1],
    [...$activation, 'verificationDueAt' => '2026-10-02T10:00:00+00:00', 'expectedRevision' => 1],
] as $invalid) {
    try {
        $service->save($invalid);
        throw new RuntimeException('Ungültige technische Aktivierung wurde akzeptiert.');
    } catch (InvalidArgumentException) {
    }
    $assertSame($beforeDenied, $repository->rows, 'Eine ungültige Aktivierung hatte Nebenwirkungen.');
}

$repository->injectConcurrentWinner = true;
try {
    $service->save([...$activation, 'expectedRevision' => 1]);
    throw new RuntimeException('Ein konkurrierender Aktivierungsvorgang wurde nicht abgewiesen.');
} catch (DomainException) {
}
$assertSame(false, $service->status()['executionAvailable'], 'Nach einem Konflikt muss die gespeicherte Deaktivierungsrevision maßgeblich bleiben.');

$disabled = $service->save(['enabled' => false, 'expectedRevision' => 2]);
$assertSame(false, $disabled['executionAvailable'], 'Eine explizite Deaktivierung wurde ignoriert.');
$assertSame('REVIEW', $disabled['action'], 'Eine explizite Deaktivierung muss REVIEW-only bleiben.');

$service->save([...$activation, 'expectedRevision' => 3]);
$clock->value = new DateTimeImmutable('2027-10-01T00:00:00+00:00');
$expired = $service->status();
$assertSame(false, $expired['executionAvailable'], 'Ein fälliger technischer Prüftermin muss fail-closed wirken.');
$assertSame(['technical_verification_due'], $expired['blockers'], 'Der veraltete technische Zustand ist nicht eindeutig diagnostizierbar.');

echo "Retention execution activation tests passed.\n";
