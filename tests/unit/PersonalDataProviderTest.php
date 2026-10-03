<?php

declare(strict_types=1);

use OCA\FilzmannDataProtection\Db\TemporaryAdminAccessRepositoryInterface;
use OCA\FilzmannDataProtection\Db\RetentionExecutionProfileRepositoryInterface;
use OCA\FilzmannDataProtection\Db\RetentionExecutionActivationRepositoryInterface;
use OCA\FilzmannDataProtection\Privacy\DataProtectionPersonalDataProvider;
use OCA\FilzmannDataProtection\Privacy\DataProtectionPersonalDataProviderListener;
use OCA\FilzmannDataProtection\PublicApi\V1\DataSubjectRef;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCA\FilzmannDataProtection\Service\AdminHistoryRetentionPolicyService;

$repository = new class implements TemporaryAdminAccessRepositoryInterface {
    public array $requests = [];
    public function replaceActive(string $targetUid, string $grantedBy, DateTimeImmutable $startsAt, DateTimeImmutable $endsAt): array { return []; }
    public function revokeActive(string $targetUid, string $revokedBy, DateTimeImmutable $revokedAt): bool { return false; }
    public function activeFor(string $targetUid, DateTimeImmutable $at): ?array { return null; }
    public function history(): array { return []; }
    public function historyForUid(string $uid, int $limit, DateTimeImmutable $asOf): array {
        $this->requests[] = [$uid, $limit, $asOf];
        return [[
            'id' => 17,
            'targetUid' => $uid,
            'grantedBy' => 'other-admin',
            'startsAt' => new DateTimeImmutable('2026-08-25T10:00:00+00:00'),
            'endsAt' => new DateTimeImmutable('2026-08-25T14:00:00+00:00'),
            'revokedAt' => null,
            'revokedBy' => null,
            'createdAt' => new DateTimeImmutable('2026-08-25T10:00:00+00:00'),
        ]];
    }
    public function endedBefore(DateTimeImmutable $cutoff, int $limit, int $offset): array { return []; }
};
$config = new class implements OCP\IAppConfig {
    public array $values = [];
    public function getValueArray(string $appId, string $key, array $default = [], bool $lazy = false): array { return $this->values[$key] ?? $default; }
    public function getValueBool(string $appId, string $key, bool $default = false, bool $lazy = false): bool { return $default; }
    public function setValueArray(string $appId, string $key, array $value, bool $lazy = false): void { $this->values[$key] = $value; }
    public function setValueBool(string $appId, string $key, bool $value, bool $lazy = false): void {}
};
$groups = new class implements OCP\IGroupManager {
    public function isAdmin(string $uid): bool { return false; }
    public function isInGroup(string $uid, string $gid): bool { return $uid === 'subject-17' && $gid === 'Datenschutzbeauftragte'; }
    public function groupExists(string $gid): bool { return true; }
};
$session = new class implements OCP\IUserSession {
    public function getUser(): ?OCP\IUser { return new class implements OCP\IUser { public function getUID(): string { return 'subject-17'; } }; }
};
$clock = new class implements OCP\AppFramework\Utility\ITimeFactory {
    public function now(): DateTimeImmutable { return new DateTimeImmutable('2026-09-25T10:00:00+00:00'); }
};
$retentionPolicy = new AdminHistoryRetentionPolicyService($config, $groups, $session, $clock);
$retentionPolicy->save(['durationPeriod' => 'P9M', 'expectedRevision' => 0]);
$executionProfiles = new class implements RetentionExecutionProfileRepositoryInterface {
    public function latest(): ?array { return null; }
    public function append(array $configuration): array { return $configuration; }
    public function historyForUid(string $uid, int $limit, int $offset, DateTimeImmutable $asOf): array { return array_slice(array_values(array_filter($this->history(), static fn(array $row): bool => $row['changedBy'] === $uid && $row['createdAt'] <= $asOf)), $offset, $limit); }
    public function history(): array {
        return [
            [
                'revision' => 1,
                'profileId' => 'employment_collective_agreement_de',
                'profileRevision' => 'profile-1',
                'effectiveAt' => new DateTimeImmutable('2026-09-25T10:00:00+00:00'),
                'legalReviewDueAt' => new DateTimeImmutable('2027-09-25T10:00:00+00:00'),
                'backupRegularDays' => 30,
                'backupBufferDays' => 5,
                'backupReviewDueAt' => new DateTimeImmutable('2027-03-25T10:00:00+00:00'),
                'changedBy' => 'subject-17',
                'createdAt' => new DateTimeImmutable('2026-09-25T10:00:00+00:00'),
            ],
            [
                'revision' => 2,
                'profileId' => 'legitimate_interest_it_security_de',
                'profileRevision' => 'profile-2',
                'effectiveAt' => new DateTimeImmutable('2026-09-25T11:00:00+00:00'),
                'legalReviewDueAt' => new DateTimeImmutable('2027-09-25T11:00:00+00:00'),
                'backupRegularDays' => 20,
                'backupBufferDays' => 2,
                'backupReviewDueAt' => new DateTimeImmutable('2027-03-25T11:00:00+00:00'),
                'changedBy' => 'other-dpo',
                'createdAt' => new DateTimeImmutable('2026-09-25T11:00:00+00:00'),
            ],
        ];
    }
};
$executionActivations = new class implements RetentionExecutionActivationRepositoryInterface {
    public function latest(): ?array { return null; }
    public function history(): array { return []; }
    public function appendIfCurrent(array $configuration, int $expectedRevision): array { return $configuration; }
    public function historyForUid(string $uid, int $limit, int $offset, DateTimeImmutable $asOf): array {
        return $uid === 'subject-17' ? [[
            'revision'=>1,
            'enabled'=>true,
            'verificationDueAt'=>new DateTimeImmutable('2027-09-25T10:00:00+00:00'),
            'changedBy'=>$uid,
            'createdAt'=>new DateTimeImmutable('2026-09-25T10:00:00+00:00'),
        ]] : [];
    }
};
$provider = new DataProtectionPersonalDataProvider($repository, $retentionPolicy, $executionProfiles, $executionActivations);
$subject = new DataSubjectRef('nextcloud-user', 'subject-17');
$page = $provider->collect(new PersonalDataRequest($subject, 'de', 'access-report', 20, []));
if ($page->status() !== 'complete' || count($page->entries()) !== 4) throw new RuntimeException('Eigene Adminfreigabe und Policybearbeitungen werden nicht vollständig ausgegeben.');
if (($repository->requests[0][0] ?? null) !== 'subject-17') throw new RuntimeException('Adminfreigaben werden nicht strikt subject-gebunden abgefragt.');
$entry = $page->entries()[0];
$payload = json_encode([
    'category' => $entry->categoryLabel(),
    'summary' => $entry->summary(),
    'attributes' => $entry->attributes(),
    'notice' => $entry->thirdPartyContentNotice(),
], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
if (!str_contains($payload, 'Admin-Vollzugriff') || !str_contains($payload, 'Ziel der Vollzugriffsfreigabe')) throw new RuntimeException('Eigener Freigabekontext fehlt.');
if (str_contains($payload, 'other-admin')) throw new RuntimeException('Kennung einer anderen Administration wurde offengelegt.');
$policyEntry = $page->entries()[1];
if ($policyEntry->reference() !== 'data-protection:retention-policy:1'
    || ($policyEntry->attributes()['Aufbewahrungsfrist'] ?? null) !== 'P9M'
    || str_contains(json_encode([$policyEntry->summary(), $policyEntry->attributes()], JSON_THROW_ON_ERROR), 'other-admin')) {
    throw new RuntimeException('Subjectgebundene Retention-Policybearbeitung fehlt oder legt fremde Kennungen offen.');
}
$profileEntry = $page->entries()[2];
if ($profileEntry->reference() !== 'data-protection:retention-execution-profile:1'
    || ($profileEntry->attributes()['Profil'] ?? null) !== 'employment_collective_agreement_de'
    || str_contains(json_encode([$profileEntry->summary(), $profileEntry->attributes()], JSON_THROW_ON_ERROR), 'other-dpo')) {
    throw new RuntimeException('Subjectgebundene Kundenprofilbearbeitung fehlt oder legt fremde DPO-Kennungen offen.');
}
$activationEntry = $page->entries()[3];
if ($activationEntry->reference() !== 'data-protection:retention-execution-activation:1'
    || ($activationEntry->attributes()['Status'] ?? null) !== 'aktiviert'
    || str_contains(json_encode([$activationEntry->summary(), $activationEntry->attributes()], JSON_THROW_ON_ERROR), 'other-admin')) {
    throw new RuntimeException('Subjectgebundene technische Aktivierungsrevision fehlt oder legt fremde Kennungen offen.');
}

$foreign = $provider->collect(new PersonalDataRequest(new DataSubjectRef('external-applicant', 'subject-17'), 'de', 'access-report', 20, []));
if ($foreign->status() !== 'not_applicable' || count($repository->requests) !== 1) throw new RuntimeException('Nicht unterstützter Subject-Typ löst eine Datenabfrage aus.');

$emptyAdminHistory = new class implements TemporaryAdminAccessRepositoryInterface {
    public function replaceActive(string $targetUid, string $grantedBy, DateTimeImmutable $startsAt, DateTimeImmutable $endsAt): array { return []; }
    public function revokeActive(string $targetUid, string $revokedBy, DateTimeImmutable $revokedAt): bool { return false; }
    public function activeFor(string $targetUid, DateTimeImmutable $at): ?array { return null; }
    public function history(): array { return []; }
    public function historyForUid(string $uid, int $limit, DateTimeImmutable $asOf): array { return []; }
    public function endedBefore(DateTimeImmutable $cutoff, int $limit, int $offset): array { return []; }
};
$manyProfiles = new class implements RetentionExecutionProfileRepositoryInterface {
    public function latest(): ?array { return null; }
    public function append(array $configuration): array { return $configuration; }
    public function history(): array { throw new RuntimeException('Art. 15 darf keine global gekappte Profilhistorie verwenden.'); }
    public function historyForUid(string $uid, int $limit, int $offset, DateTimeImmutable $asOf): array {
        $rows = [];
        for ($revision = 1; $revision <= 201; $revision++) {
            $rows[] = [
                'revision' => $revision,
                'profileId' => 'employment_collective_agreement_de',
                'profileRevision' => 'profile-' . $revision,
                'effectiveAt' => new DateTimeImmutable('2026-09-01T10:00:00+00:00'),
                'legalReviewDueAt' => new DateTimeImmutable('2027-09-01T10:00:00+00:00'),
                'backupRegularDays' => 30,
                'backupBufferDays' => 5,
                'backupReviewDueAt' => new DateTimeImmutable('2027-03-01T10:00:00+00:00'),
                'changedBy' => $uid,
                'createdAt' => new DateTimeImmutable('2026-09-01T10:00:00+00:00'),
            ];
        }
        return array_slice($rows, $offset, $limit);
    }
};
$blankConfig = clone $config;
$blankConfig->values = [];
$pagedProvider = new DataProtectionPersonalDataProvider(
    $emptyAdminHistory,
    new AdminHistoryRetentionPolicyService($blankConfig, $groups, $session, $clock),
    $manyProfiles,
    new class implements RetentionExecutionActivationRepositoryInterface {
        public function latest(): ?array { return null; }
        public function history(): array { return []; }
        public function appendIfCurrent(array $configuration, int $expectedRevision): array { return $configuration; }
        public function historyForUid(string $uid, int $limit, int $offset, DateTimeImmutable $asOf): array { return []; }
    },
);
$firstProfilePage = $pagedProvider->collect(new PersonalDataRequest($subject, 'de', 'access-report', 200, []));
if ($firstProfilePage->status() !== 'partial' || count($firstProfilePage->entries()) !== 200 || $firstProfilePage->nextCursor() === null) {
    throw new RuntimeException('Mehr als 200 eigene Profilrevisionen werden nicht transparent paginiert.');
}
$secondRequest = (new PersonalDataRequest(
    $subject,
    'de',
    'access-report',
    200,
    ['filzmann_data_protection' => $firstProfilePage->nextCursor()],
))->forProvider('filzmann_data_protection', 200);
$secondProfilePage = $pagedProvider->collect($secondRequest);
if ($secondProfilePage->status() !== 'complete' || count($secondProfilePage->entries()) !== 1) {
    throw new RuntimeException('Eigene Profilrevisionen jenseits der ersten 200 werden still ausgelassen.');
}

$event = new RegisterPersonalDataProvidersEvent();
(new DataProtectionPersonalDataProviderListener($provider))->handle($event);
if (($event->providers()['filzmann_data_protection'] ?? null) !== $provider) throw new RuntimeException('Eigener PersonalDataProvider wird nicht lazy registriert.');

echo "Data Protection personal data provider tests passed.\n";
