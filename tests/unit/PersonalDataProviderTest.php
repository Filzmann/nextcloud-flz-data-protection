<?php

declare(strict_types=1);

use OCA\FilzmannDataProtection\Db\TemporaryAdminAccessRepositoryInterface;
use OCA\FilzmannDataProtection\Privacy\DataProtectionPersonalDataProvider;
use OCA\FilzmannDataProtection\Privacy\DataProtectionPersonalDataProviderListener;
use OCA\FilzmannDataProtection\PublicApi\V1\DataSubjectRef;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;

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
};
$provider = new DataProtectionPersonalDataProvider($repository);
$subject = new DataSubjectRef('nextcloud-user', 'subject-17');
$page = $provider->collect(new PersonalDataRequest($subject, 'de', 'access-report', 20, []));
if ($page->status() !== 'complete' || count($page->entries()) !== 1) throw new RuntimeException('Eigene Adminfreigabe wird nicht vollständig ausgegeben.');
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

$foreign = $provider->collect(new PersonalDataRequest(new DataSubjectRef('external-applicant', 'subject-17'), 'de', 'access-report', 20, []));
if ($foreign->status() !== 'not_applicable' || count($repository->requests) !== 1) throw new RuntimeException('Nicht unterstützter Subject-Typ löst eine Datenabfrage aus.');

$event = new RegisterPersonalDataProvidersEvent();
(new DataProtectionPersonalDataProviderListener($provider))->handle($event);
if (($event->providers()['filzmann_data_protection'] ?? null) !== $provider) throw new RuntimeException('Eigener PersonalDataProvider wird nicht lazy registriert.');

echo "Data Protection personal data provider tests passed.\n";
