<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Privacy;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use OCA\FilzmannDataProtection\Db\TemporaryAdminAccessRepositoryInterface;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataEntry;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataPage;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataProvider;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FilzmannDataProtection\PublicApi\V1\ProviderDescriptor;

final class DataProtectionPersonalDataProvider implements PersonalDataProvider {
    private const MAX_PAGE_SIZE = 200;
    private const MAX_OFFSET = 1000000;

    public function __construct(private TemporaryAdminAccessRepositoryInterface $adminAccess) {
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
        $rows = $this->adminAccess->historyForUid(
            $request->subject()->subjectId(),
            $offset + $limit + 1,
            new DateTimeImmutable($asOf),
        );
        $pageRows = array_slice($rows, $offset, $limit + 1);
        $hasMore = count($pageRows) > $limit;
        if ($hasMore) {
            $pageRows = array_slice($pageRows, 0, $limit);
        }
        if ($pageRows === [] && $offset === 0) {
            return new PersonalDataPage('not_applicable');
        }

        $subjectUid = $request->subject()->subjectId();
        return new PersonalDataPage(
            $hasMore ? 'partial' : 'complete',
            array_map(fn(array $row): PersonalDataEntry => $this->entry($row, $subjectUid), $pageRows),
            $hasMore ? ['Weitere eigene Adminfreigaben sind auf einer Folgeseite verfügbar.'] : [],
            $hasMore ? $this->encodeCursor($asOf, $offset + $limit) : null,
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
