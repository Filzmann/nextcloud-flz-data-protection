<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Privacy;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use OCA\FilzmannDataProtection\Db\TemporaryAdminAccessRepositoryInterface;
use OCA\FilzmannDataProtection\Db\RetentionExecutionProfileRepositoryInterface;
use OCA\FilzmannDataProtection\Db\RetentionExecutionActivationRepositoryInterface;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataEntry;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataPage;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataProvider;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FilzmannDataProtection\PublicApi\V1\ProviderDescriptor;
use OCA\FilzmannDataProtection\Service\AdminHistoryRetentionPolicyService;

final class DataProtectionPersonalDataProvider implements PersonalDataProvider {
    private const MAX_PAGE_SIZE = 200;
    private const MAX_OFFSET = 1000000;

    public function __construct(
        private TemporaryAdminAccessRepositoryInterface $adminAccess,
        private AdminHistoryRetentionPolicyService $retentionPolicy,
        private RetentionExecutionProfileRepositoryInterface $executionProfiles,
        private RetentionExecutionActivationRepositoryInterface $executionActivations,
    ) {
    }

    public function descriptor(): ProviderDescriptor {
        return new ProviderDescriptor(
            'filzmann_data_protection',
            'Datenschutz-Center',
            '1.0',
            ['nextcloud-user'],
            ['personal-data'],
            self::MAX_PAGE_SIZE,
        );
    }

    public function collect(PersonalDataRequest $request): PersonalDataPage {
        if ($request->subject()->subjectType() !== 'nextcloud-user') {
            return new PersonalDataPage('not_applicable');
        }

        [$asOf, $offset] = $this->cursorState($request->cursor());
        $limit = min($request->pageLimit(), self::MAX_PAGE_SIZE);
        $subjectUid = $request->subject()->subjectId();
        $rows = $this->adminAccess->historyForUid(
            $subjectUid,
            $offset + $limit + 1,
            new DateTimeImmutable($asOf),
        );
        $entries = array_map(fn(array $row): PersonalDataEntry => $this->entry($row, $subjectUid), $rows);
        foreach ($this->retentionPolicy->history() as $policyEntry) {
            $eventAt = $policyEntry['reviewedAt'] ?? $policyEntry['effectiveAt'] ?? null;
            if ($policyEntry['changedBy'] === $subjectUid && is_string($eventAt) && $eventAt <= $asOf) {
                $entries[] = $this->policyEntry($policyEntry);
            }
        }
        foreach ($this->executionProfiles->historyForUid(
            $subjectUid,
            $offset + $limit + 1,
            0,
            new DateTimeImmutable($asOf),
        ) as $profileEntry) {
            $entries[] = $this->executionProfileEntry($profileEntry);
        }
        foreach ($this->executionActivations->historyForUid(
            $subjectUid,
            $offset + $limit + 1,
            0,
            new DateTimeImmutable($asOf),
        ) as $activationEntry) {
            $entries[] = $this->executionActivationEntry($activationEntry);
        }
        $pageEntries = array_slice($entries, $offset, $limit + 1);
        $hasMore = count($pageEntries) > $limit;
        if ($hasMore) $pageEntries = array_slice($pageEntries, 0, $limit);
        if ($pageEntries === [] && $offset === 0) {
            return new PersonalDataPage('not_applicable');
        }

        return new PersonalDataPage(
            $hasMore ? 'partial' : 'complete',
            $pageEntries,
            $hasMore ? ['Weitere eigene Adminfreigaben sind auf einer Folgeseite verfügbar.'] : [],
            $hasMore ? $this->encodeCursor($asOf, $offset + $limit) : null,
        );
    }

    private function policyEntry(array $entry): PersonalDataEntry {
        return new PersonalDataEntry(
            categoryId: 'retention-policy',
            categoryLabel: 'Aufbewahrungsregel',
            reference: 'data-protection:retention-policy:' . (string)$entry['revision'],
            summary: $entry['event'] === 'configured' ? 'Aufbewahrungsregel konfiguriert' : 'Aufbewahrungsregel geprüft',
            purpose: 'Nachweis der Konfiguration und regelmäßigen Prüfung der Aufbewahrungsregel',
            source: 'Eigene Eingabe in der Datenschutzkonfiguration des Datenschutz-Centers',
            recipientCategories: ['Betroffene Person und ausdrücklich berechtigte Datenschutz-Prüfrolle'],
            retention: 'Keine feste Löschfrist für die Policyhistorie festgelegt.',
            thirdCountryTransfer: 'Durch das Datenschutz-Center sind keine Drittlandübermittlungen vorgesehen.',
            automatedDecision: 'Die Regel erzeugt ausschließlich eine manuelle Prüfungsvorschau und keine automatische Löschung.',
            thirdPartyContentNotice: 'Die Policyhistorie enthält in dieser Auskunft keine Kennungen anderer Personen.',
            attributes: [
                'Aufbewahrungsfrist' => $entry['durationPeriod'],
                'Wirksam seit' => $entry['effectiveAt'] ?? 'noch nicht wirksam gesetzt',
                'Zuletzt geprüft' => $entry['reviewedAt'] ?? 'noch nicht geprüft',
            ],
        );
    }

    private function executionProfileEntry(array $entry): PersonalDataEntry {
        return new PersonalDataEntry(
            categoryId: 'retention-execution-profile',
            categoryLabel: 'Rechts- und Backup-Profil',
            reference: 'data-protection:retention-execution-profile:' . (string)$entry['revision'],
            summary: 'Kundenprofilrevision gespeichert',
            purpose: 'Nachweis der DPO-bestätigten Rechts-, Backup- und Restore-Konfiguration',
            source: 'Eigene Eingabe in der geschützten Datenschutzkonfiguration',
            recipientCategories: ['Betroffene Person und aktuelle Mitglieder von Datenschutzbeauftragte'],
            retention: 'Die revisionsgeführte Profilhistorie besitzt noch keinen freigegebenen ausführenden Löschpfad.',
            thirdCountryTransfer: 'Durch das Datenschutz-Center sind keine Drittlandübermittlungen vorgesehen.',
            automatedDecision: 'Die Profilkonfiguration aktiviert keine Retention-Ausführung und bleibt REVIEW-only.',
            thirdPartyContentNotice: 'Die Projektion enthält keine Kennungen anderer Datenschutzbeauftragter und keine externen Evidenzinhalte.',
            attributes: [
                'Profil' => $entry['profileId'],
                'Profilrevision' => $entry['profileRevision'],
                'Wirksam seit' => $entry['effectiveAt']->format(DATE_ATOM),
                'Nächste Rechtsprüfung' => $entry['legalReviewDueAt']->format(DATE_ATOM),
                'Backupgrenze' => $entry['backupRegularDays'] . '+' . $entry['backupBufferDays'] . ' Tage',
                'Nächste Backupprüfung' => $entry['backupReviewDueAt']->format(DATE_ATOM),
            ],
        );
    }

    private function executionActivationEntry(array $entry): PersonalDataEntry {
        return new PersonalDataEntry(
            categoryId: 'retention-execution-activation',
            categoryLabel: 'Technische Retention-Aktivierung',
            reference: 'data-protection:retention-execution-activation:' . (string)$entry['revision'],
            summary: ($entry['enabled'] ?? false) === true ? 'Automatische Löschung technisch aktiviert' : 'Automatische Löschung technisch deaktiviert',
            purpose: 'Nachweis des technischen Betriebszustands der automatischen Retention',
            source: 'Eigene Eingabe in der Nextcloud-Administration',
            recipientCategories: ['Betroffene Person und Nextcloud-Administration'],
            retention: 'Technische Aktivierungsrevisionen werden 24 Monate ab Ablösung aufbewahrt.',
            thirdCountryTransfer: 'Durch das Datenschutz-Center sind keine Drittlandübermittlungen vorgesehen.',
            automatedDecision: 'Die Revision steuert nur den technischen Hintergrundjob; sie bewertet keine Person.',
            thirdPartyContentNotice: 'Die Projektion enthält keine Kennungen anderer Operatoren und keine Rechts- oder Evidenzdaten.',
            attributes: [
                'Status' => ($entry['enabled'] ?? false) === true ? 'aktiviert' : 'deaktiviert',
                'Gespeichert am' => $entry['createdAt']->format(DATE_ATOM),
                'Nächste technische Prüfung' => $entry['verificationDueAt']?->format(DATE_ATOM) ?? 'nicht anwendbar',
            ],
        );
    }

    private function entry(array $row, string $subjectUid): PersonalDataEntry {
        $roles = [];
        if ($row['targetUid'] === $subjectUid) $roles[] = 'Ziel der Vollzugriffsfreigabe';
        if ($row['grantedBy'] === $subjectUid) $roles[] = 'Freigebende Administration';
        if ($row['revokedBy'] === $subjectUid) $roles[] = 'Widerrufende Administration';
        $actualEnd = $row['revokedAt'] ?? $row['endsAt'];

        return new PersonalDataEntry(
            categoryId: 'admin-access',
            categoryLabel: 'Zeitlich begrenzter Admin-Vollzugriff',
            reference: 'data-protection:admin-access:' . (string)$row['id'],
            summary: 'Admin-Vollzugriff vom ' . $row['startsAt']->format('d.m.Y, H:i'),
            purpose: 'Nachweis einer zeitlich begrenzten fachlichen Datenschutz-Freigabe',
            source: 'App-lokale Freigabe im Nextcloud-Adminbereich',
            recipientCategories: ['Nextcloud-Administrator*innen'],
            retention: 'Keine feste Löschfrist festgelegt; die sicherheitsrelevante Freigabehistorie bleibt bis zu einer gesonderten Aufbewahrungsentscheidung erhalten.',
            thirdCountryTransfer: 'Durch das Datenschutz-Center sind keine Drittlandübermittlungen vorgesehen.',
            automatedDecision: 'Der Server beendet den Vollzugriff spätestens nach 24 Stunden automatisch.',
            thirdPartyContentNotice: 'Kennungen anderer beteiligter Administrator*innen werden nicht ausgegeben.',
            attributes: [
                'Eigene Rolle im Vorgang' => implode(', ', $roles),
                'Beginn' => $row['startsAt']->format(DATE_ATOM),
                'Geplantes Ende' => $row['endsAt']->format(DATE_ATOM),
                'Tatsächliches Ende' => $actualEnd->format(DATE_ATOM),
                'Status' => $row['revokedAt'] === null ? 'planmäßig beendet oder noch aktiv' : 'widerrufen',
            ],
        );
    }

    private function cursorState(?string $cursor): array {
        if ($cursor === null) {
            return [(new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM), 0];
        }
        $padding = (4 - strlen($cursor) % 4) % 4;
        $json = base64_decode(strtr($cursor . str_repeat('=', $padding), '-_', '+/'), true);
        $state = $json === false ? null : json_decode($json, true);
        if (!is_array($state) || !is_string($state['asOf'] ?? null) || !is_int($state['offset'] ?? null) || $state['offset'] < 1 || $state['offset'] > self::MAX_OFFSET) {
            throw new InvalidArgumentException('Invalid Data Protection privacy cursor.');
        }
        try {
            $asOf = (new DateTimeImmutable($state['asOf']))->format(DATE_ATOM);
        } catch (\Throwable) {
            throw new InvalidArgumentException('Invalid Data Protection privacy cursor.');
        }
        return [$asOf, $state['offset']];
    }

    private function encodeCursor(string $asOf, int $offset): string {
        return rtrim(strtr(base64_encode(json_encode(['asOf' => $asOf, 'offset' => $offset], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }
}
