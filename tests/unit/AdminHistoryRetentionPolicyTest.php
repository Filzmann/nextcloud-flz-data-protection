<?php

declare(strict_types=1);

namespace OCP {
    interface IUser { public function getUID(): string; }
    interface IAppConfig {
        public function getValueArray(string $appId, string $key, array $default = [], bool $lazy = false): array;
        public function setValueArray(string $appId, string $key, array $value, bool $lazy = false): void;
    }
    interface IGroupManager { public function isInGroup(string $uid, string $gid): bool; }
    interface IUserSession { public function getUser(): ?IUser; }
}
namespace OCP\AppFramework\Utility { interface ITimeFactory { public function now(): \DateTimeImmutable; } }

namespace {
    use OCA\FilzmannDataProtection\Db\TemporaryAdminAccessRepositoryInterface;
    use OCA\FilzmannDataProtection\Privacy\AdminHistoryRetentionProvider;
    use OCA\FilzmannDataProtection\PublicApi\V1\RetentionPreviewRequest;
    use OCA\FilzmannDataProtection\Service\AdminHistoryRetentionPolicyService;

    $config = new class implements OCP\IAppConfig {
        public array $values = [];
        public function getValueArray(string $appId, string $key, array $default = [], bool $lazy = false): array { return $this->values[$key] ?? $default; }
        public function setValueArray(string $appId, string $key, array $value, bool $lazy = false): void { $this->values[$key] = $value; }
    };
    $groups = new class implements OCP\IGroupManager {
        public function isInGroup(string $uid, string $gid): bool { return $uid === 'dpo' && $gid === 'Datenschutzbeauftragte'; }
    };
    $session = new class implements OCP\IUserSession {
        public string $uid = 'dpo';
        public function getUser(): ?OCP\IUser { return new class($this->uid) implements OCP\IUser { public function __construct(private string $uid) {} public function getUID(): string { return $this->uid; } }; }
    };
    $clock = new class implements OCP\AppFramework\Utility\ITimeFactory {
        public DateTimeImmutable $value;
        public function __construct() { $this->value = new DateTimeImmutable('2026-09-25T10:00:00+00:00'); }
        public function now(): DateTimeImmutable { return $this->value; }
    };

    $service = new AdminHistoryRetentionPolicyService($config, $groups, $session, $clock);
    $default = $service->policy();
    if ($default['durationPeriod'] !== 'P6M' || $default['action'] !== 'REVIEW' || $default['revision'] !== 0 || !$default['reviewDue']) {
        throw new RuntimeException('Die beschlossene Sechsmonatsfrist ist nicht der sichere versionierte Standard.');
    }
    $saved = $service->save(['durationPeriod' => 'P9M', 'expectedRevision' => 0]);
    if ($saved['revision'] !== 1 || $saved['changedBy'] !== 'dpo' || count($service->history()) !== 1) {
        throw new RuntimeException('Die Adminhistorien-Policy wird nicht versioniert protokolliert.');
    }

    $before = $config->values;
    $session->uid = 'ordinary';
    try { $service->save(['durationPeriod' => 'P1Y', 'expectedRevision' => 1]); throw new RuntimeException('Nicht-DPO konnte Policy ändern.'); }
    catch (DomainException) {}
    if ($config->values !== $before) throw new RuntimeException('Verweigerte Änderung hatte Nebenwirkungen.');

    $session->uid = 'dpo';
    $clock->value = new DateTimeImmutable('2027-09-26T10:00:00+00:00');
    if (!$service->reviewDue()) throw new RuntimeException('Jährlicher Reviewstatus wird nicht fällig.');
    $reviewed = $service->recordReview(1);
    if ($reviewed['revision'] !== 2 || $reviewed['event'] !== 'reviewed' || $service->reviewDue()) {
        throw new RuntimeException('Review wird nicht als eigene Version protokolliert.');
    }

    $repository = new class implements TemporaryAdminAccessRepositoryInterface {
        public array $previewRequests = [];
        public function replaceActive(string $targetUid, string $grantedBy, DateTimeImmutable $startsAt, DateTimeImmutable $endsAt): array { return []; }
        public function revokeActive(string $targetUid, string $revokedBy, DateTimeImmutable $revokedAt): bool { return false; }
        public function activeFor(string $targetUid, DateTimeImmutable $at): ?array { return null; }
        public function history(): array { return []; }
        public function historyForUid(string $uid, int $limit, DateTimeImmutable $asOf): array { return []; }
        public function endedBefore(DateTimeImmutable $cutoff, int $limit, int $offset): array {
            $this->previewRequests[] = [$cutoff->format(DATE_ATOM), $limit, $offset];
            return [[
                'id' => 7,
                'targetUid' => 'admin-target',
                'grantedBy' => 'dpo',
                'startsAt' => new DateTimeImmutable('2026-01-01T09:00:00+00:00'),
                'endsAt' => new DateTimeImmutable('2026-01-01T12:00:00+00:00'),
                'revokedAt' => new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
                'revokedBy' => 'dpo',
                'createdAt' => new DateTimeImmutable('2026-01-01T09:00:00+00:00'),
            ]];
        }
    };
    $provider = new AdminHistoryRetentionProvider($repository, $service);
    $policies = $provider->policies();
    if (count($policies) !== 1 || ($policies[0]->toArray()['version'] ?? null) !== '1.2' || $policies[0]->action() !== 'REVIEW') {
        throw new RuntimeException('Die versionierte Adminhistorien-Policy wird nicht verlustfrei als REVIEW projiziert.');
    }
    $preview = $provider->preview(new RetentionPreviewRequest(
        AdminHistoryRetentionProvider::POLICY_ID,
        '2028-07-01T10:00:00+00:00',
        20,
    ));
    if (($repository->previewRequests[0][0] ?? null) !== '2027-10-01T10:00:00+00:00'
        || $preview->status() !== 'complete'
        || ($preview->candidates()[0]->toArray()['occurredAt'] ?? null) !== '2026-01-01T10:00:00+00:00') {
        throw new RuntimeException('Die aktuelle Frist wird nicht rückwirkend vom tatsächlichen Freigabeende ausgewertet.');
    }
    $requestCount = count($repository->previewRequests);
    if ($provider->preview(new RetentionPreviewRequest('unknown_policy', '2028-07-01T10:00:00+00:00', 20))->status() !== 'not_applicable'
        || count($repository->previewRequests) !== $requestCount) {
        throw new RuntimeException('Eine unbekannte Policy-ID löst eine Adminhistorienabfrage aus.');
    }
    if (method_exists($provider, 'execute')) {
        throw new RuntimeException('Der V1-Provider darf vor Abschluss von DP-07 keinen Ausführungspfad anbieten.');
    }

    $config->values['admin_history_retention_policy_v1'] = [['revision' => 99]];
    $before = $config->values;
    try { $service->save(['durationPeriod' => 'P6M', 'expectedRevision' => 0]); throw new RuntimeException('Beschädigte Policyhistorie wurde überschrieben.'); }
    catch (DomainException) {}
    if ($config->values !== $before) throw new RuntimeException('Beschädigte Policyhistorie wurde verändert.');

    echo "Data Protection admin-history retention policy test passed.\n";
}
