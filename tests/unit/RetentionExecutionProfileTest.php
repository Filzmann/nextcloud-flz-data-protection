<?php

declare(strict_types=1);

use OCA\FilzmannDataProtection\Db\RetentionExecutionProfileRepositoryInterface;
use OCA\FilzmannDataProtection\Controller\RetentionExecutionProfileController;
use OCA\FilzmannDataProtection\Service\RetentionExecutionProfileService;
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

$repository = new class implements RetentionExecutionProfileRepositoryInterface {
    public array $rows = [];
    public function latest(): ?array { return $this->rows === [] ? null : $this->rows[array_key_last($this->rows)]; }
    public function history(): array { return $this->rows; }
    public function historyForUid(string $uid, int $limit, int $offset, DateTimeImmutable $asOf): array { return array_slice(array_values(array_filter($this->rows, static fn(array $row): bool => ($row['changedBy'] ?? null) === $uid && ($row['createdAt'] ?? $asOf) <= $asOf)), $offset, $limit); }
    public function append(array $configuration): array { $this->rows[] = $configuration; return $configuration; }
};
$groups = new class implements IGroupManager {
    public function isAdmin(string $uid): bool { return in_array($uid, ['native-admin', 'temporary-admin'], true); }
    public function isInGroup(string $uid, string $gid): bool { return $uid === 'dpo' && $gid === 'Datenschutzbeauftragte'; }
    public function groupExists(string $gid): bool { return $gid === 'Datenschutzbeauftragte'; }
};
$session = new class implements IUserSession {
    public string $uid = 'dpo';
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

$service = new RetentionExecutionProfileService($repository, $groups, $session, $clock);
$empty = $service->status();
$assertSame('REVIEW', $empty['action'], 'Eine leere Installation muss REVIEW-only starten.');
$assertSame(false, $empty['configurationValid'], 'Eine leere Installation darf nicht als konfiguriert gelten.');
$assertSame(false, $empty['executionAvailable'], 'Die Konfiguration darf keinen Ausführungspfad aktivieren.');
$assertSame(true, $empty['performanceMonitoringProhibited'], 'Das Verbot der Leistungs- und Verhaltenskontrolle ist keine Kundenoption.');

$collective = [
    'profileId' => 'employment_collective_agreement_de',
    'profileRevision' => 'SYNTH-COLLECTIVE-REV-1',
    'legalEvidenceReference' => 'SYNTH-LEGAL-EVIDENCE-1',
    'scopeReference' => 'Beschäftigte Administrationskonten',
    'allAccountsEmployeesConfirmed' => true,
    'purposeReference' => 'IT-Sicherheit und Zugriffsnachweis',
    'necessityAssessmentReference' => '',
    'impactAssessmentReference' => '',
    'safeguardsReference' => 'Keine Leistungs- oder Verhaltenskontrolle',
    'accountCategories' => 'Beschäftigte Administratorinnen und Administratoren',
    'effectiveAt' => '2026-10-01T10:00:00+00:00',
    'legalReviewDueAt' => '2027-09-30T00:00:00+00:00',
    'backupRegularDays' => 30,
    'backupBufferDays' => 5,
    'backupResponsibleParty' => 'Betriebliche IKT',
    'backupScope' => 'Nextcloud-Datenbanksicherung',
    'backupEvidenceReference' => 'BACKUP-EVIDENCE-2026-09',
    'backupEvidenceAt' => '2026-09-30T12:00:00+00:00',
    'backupReviewDueAt' => '2027-03-31T00:00:00+00:00',
    'restoreTestReference' => 'RESTORE-TEST-2026-09',
    'restoreTestedAt' => '2026-09-30T14:00:00+00:00',
    'dpoConfirmed' => true,
    'expectedRevision' => 0,
];

$saved = $service->save($collective);
$assertSame(1, $saved['configuration']['revision'], 'Die erste gültige Konfiguration muss Revision 1 erhalten.');
$assertSame('dpo', $saved['configuration']['changedBy'], 'Der serverseitige DPO-Akteur muss auditiert werden.');
$assertSame(true, $saved['configurationValid'], 'Eine vollständige und aktuelle Konfiguration muss als gültig erkannt werden.');
$assertSame('REVIEW', $saved['action'], 'Auch eine gültige Konfiguration darf keine Löschung aktivieren.');
$assertSame(false, $saved['executionAvailable'], 'Im Konfigurationsschritt darf kein ausführender Pfad entstehen.');

$controller = new RetentionExecutionProfileController(new class implements IRequest {}, $service);
$assertSame(200, $controller->show()->getStatus(), 'Der DPO-geschützte Controller liefert den Profilstatus nicht aus.');
$controllerRows = $repository->rows;
$session->uid = 'native-admin';
$assertSame(403, $controller->show()->getStatus(), 'Native Administration konnte den DPO-Profilstatus lesen.');
$assertSame(403, $controller->save([...$collective, 'expectedRevision' => 1])->getStatus(), 'Native Administration konnte den DPO-Profilstatus ändern.');
$assertSame($controllerRows, $repository->rows, 'Eine controllerseitig verweigerte Profilmutation hatte Nebenwirkungen.');
$session->uid = 'dpo';

$before = $repository->rows;
foreach (['ordinary', 'native-admin', 'temporary-admin'] as $uid) {
    $session->uid = $uid;
    try {
        $service->save([...$collective, 'expectedRevision' => 1]);
        throw new RuntimeException('Nicht-DPO konnte die Ausführungskonfiguration ändern: ' . $uid);
    } catch (DomainException) {
    }
    $assertSame($before, $repository->rows, 'Eine verweigerte Mutation hatte Nebenwirkungen: ' . $uid);
}

$session->uid = 'dpo';
$invalidCases = [
    [...$collective, 'profileId' => 'free_text_legal_basis', 'expectedRevision' => 1],
    [...$collective, 'backupRegularDays' => 31, 'expectedRevision' => 1],
    [...$collective, 'backupBufferDays' => 6, 'expectedRevision' => 1],
    [...$collective, 'backupRegularDays' => 30, 'backupBufferDays' => 6, 'expectedRevision' => 1],
    [...$collective, 'dpoConfirmed' => false, 'expectedRevision' => 1],
    [...$collective, 'performanceMonitoringProhibited' => false, 'expectedRevision' => 1],
    [...$collective, 'allAccountsEmployeesConfirmed' => false, 'expectedRevision' => 1],
    [...$collective, 'legalEvidenceReference' => '', 'expectedRevision' => 1],
    [...$collective, 'effectiveAt' => '2026-09-30T00:00:00+00:00', 'expectedRevision' => 1],
    [...$collective, 'legalReviewDueAt' => '2026-10-01T10:00:00+00:00', 'expectedRevision' => 1],
    [...$collective, 'expectedRevision' => 0],
];
foreach ($invalidCases as $index => $invalid) {
    try {
        $service->save($invalid);
        throw new RuntimeException('Ungültige Konfiguration wurde akzeptiert: ' . $index);
    } catch (DomainException|InvalidArgumentException) {
    }
    $assertSame($before, $repository->rows, 'Ungültige Konfiguration hatte Nebenwirkungen: ' . $index);
}

$legitimateInterest = [
    ...$collective,
    'profileId' => 'legitimate_interest_it_security_de',
    'profileRevision' => 'LIA-2026-01',
    'legalEvidenceReference' => 'LIA-ITSEC-2026-01',
    'allAccountsEmployeesConfirmed' => false,
    'necessityAssessmentReference' => 'LIA-NECESSITY-1',
    'impactAssessmentReference' => 'LIA-IMPACT-1',
    'safeguardsReference' => 'LIA-SAFEGUARDS-1',
    'expectedRevision' => 1,
];
$savedInterest = $service->save($legitimateInterest);
$assertSame(2, $savedInterest['configuration']['revision'], 'Das zweite geschlossene Profil muss revisionsgesichert gespeichert werden.');

$validLatest = $repository->rows[1];
$repository->rows[1]['legalEvidenceReference'] = '';
$assertSame(false, $service->status()['configurationValid'], 'Ein beschädigter persistierter Pflichtnachweis muss fail-closed werden.');
$assertSame('REVIEW', $service->status()['action'], 'Ein beschädigter persistierter Stand darf nie mehr als REVIEW liefern.');
$repository->rows[1] = $validLatest;
foreach (['effectiveAt', 'backupEvidenceAt', 'restoreTestedAt'] as $missingDate) {
    unset($repository->rows[1][$missingDate]);
    $assertSame(false, $service->status()['configurationValid'], 'Ein fehlender persistierter Pflichtzeitpunkt muss fail-closed werden: ' . $missingDate);
    $repository->rows[1] = $validLatest;
}
$repository->rows[1]['backupEvidenceAt'] = new DateTimeImmutable('2026-10-02T00:00:00+00:00');
$assertSame(false, $service->status()['configurationValid'], 'Ein zukünftiger persistierter Backupnachweis muss fail-closed werden.');
$repository->rows[1] = $validLatest;
$repository->rows[1]['restoreTestedAt'] = new DateTimeImmutable('2026-10-02T00:00:00+00:00');
$assertSame(false, $service->status()['configurationValid'], 'Ein zukünftiger persistierter Restorenachweis muss fail-closed werden.');
$repository->rows[1] = $validLatest;

$clock->value = new DateTimeImmutable('2027-10-01T00:00:00+00:00');
$stale = $service->status();
$assertSame(false, $stale['configurationValid'], 'Ein fälliger Review muss fail-closed werden.');
$assertSame('REVIEW', $stale['action'], 'Ein fälliger Review darf nie mehr als REVIEW liefern.');
$assertSame(false, $stale['executionAvailable'], 'Ein fälliger Review darf keinen Ausführungspfad eröffnen.');

if (method_exists($service, 'execute') || method_exists($service, 'delete')) {
    throw new RuntimeException('Der Konfigurationsservice darf keinen Retention-Ausführungspfad enthalten.');
}

echo "Retention execution profile tests passed.\n";
