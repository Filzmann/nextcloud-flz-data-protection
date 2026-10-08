<?php

declare(strict_types=1);

namespace {
    use OCA\FlzDataProtection\Service\RetentionAccessService;
    use OCA\FlzDataProtection\Service\RetentionSettingsService;
    use OCA\FlzDataProtection\Service\TemporaryAdminAccessChecker;
    use OCP\IAppConfig;
    use OCP\IGroupManager;
    use OCP\IUser;
    use OCP\IUserSession;

    $assertSame = static function (mixed $expected, mixed $actual, string $message): void {
        if ($expected !== $actual) throw new RuntimeException($message);
    };

    $store = new class implements IAppConfig {
        public array $values = [];
        public function getValueArray(string $appId, string $key, array $default = [], bool $lazy = false): array { return $this->values[$key] ?? $default; }
        public function getValueBool(string $appId, string $key, bool $default = false, bool $lazy = false): bool { return $this->values[$key] ?? $default; }
        public function setValueArray(string $appId, string $key, array $value, bool $lazy = false): void { $this->values[$key] = $value; }
        public function setValueBool(string $appId, string $key, bool $value, bool $lazy = false): void { $this->values[$key] = $value; }
    };
    $groups = new class implements IGroupManager {
        public array $admins = ['admin-user'];
        public array $memberships = ['privacy-user' => ['Datenschutzbeauftragte']];
        public function isAdmin(string $uid): bool { return in_array($uid, $this->admins, true); }
        public function isInGroup(string $uid, string $gid): bool { return in_array($gid, $this->memberships[$uid] ?? [], true); }
        public function groupExists(string $gid): bool { return in_array($gid, ['Datenschutzbeauftragte', 'IKT-Ausschuss'], true); }
    };
    $sessionFor = static function (?string $uid): IUserSession {
        return new class($uid) implements IUserSession {
            public function __construct(private ?string $uid) {}
            public function getUser(): ?IUser {
                if ($this->uid === null) return null;
                return new class($this->uid) implements IUser {
                    public function __construct(private string $uid) {}
                    public function getUID(): string { return $this->uid; }
                };
            }
        };
    };

    $settings = new RetentionSettingsService($store, $groups);
    $assertSame(['Datenschutzbeauftragte'], $settings->reviewerGroups(), 'Die dedizierte Datenschutzgruppe muss standardmäßig vorgesehen sein.');
    $grants = new class implements TemporaryAdminAccessChecker {
        public array $active = [];
        public function hasActiveGrant(string $uid): bool { return in_array($uid, $this->active, true); }
    };
    $accessFor = static fn(?string $uid): RetentionAccessService => new RetentionAccessService($sessionFor($uid), $groups, $settings, $grants);
    $assertSame(false, $accessFor('admin-user')->canReview(), 'Native Admins dürfen ohne app-lokale Freigabe kein REVIEW lesen.');
    $grants->active = ['admin-user'];
    $assertSame(true, $accessFor('admin-user')->canReview(), 'Eine aktive, UID-genaue Adminfreigabe muss REVIEW-Zugriff erteilen.');
    $grants->active = [];
    $assertSame(true, $accessFor('privacy-user')->canReview(), 'Die konfigurierte Prüfgruppe erhält keinen REVIEW-Zugriff.');
    $assertSame(false, $accessFor('ordinary-user')->canReview(), 'Ein gewöhnliches Konto erhält REVIEW-Zugriff.');
    $assertSame(false, $accessFor(null)->canReview(), 'Anonyme Aufrufe erhalten REVIEW-Zugriff.');

    $saved = $settings->save(['reviewer_groups' => ['IKT-Ausschuss']]);
    $assertSame(['IKT-Ausschuss'], $saved['reviewer_groups'], 'Die dedizierten Prüfgruppen sind nicht konfigurierbar.');
    $assertSame(false, $accessFor('admin-user')->canReview(), 'Native Administration bleibt ohne Zeitfreigabe fachlich ausgeschlossen.');
    $assertSame(true, $accessFor('admin-user')->canConfigure(), 'Admins müssen die technische Konfiguration ohne fachliches Leserecht verwalten können.');

    $writesBefore = $store->values;
    try {
        $settings->save(['reviewer_groups' => ['Unbekannte Gruppe']]);
        throw new RuntimeException('Eine unbekannte Prüfgruppe wurde akzeptiert.');
    } catch (InvalidArgumentException) {
    }
    $assertSame($writesBefore, $store->values, 'Eine abgelehnte Konfiguration hat Teiländerungen hinterlassen.');

    echo "Retention access tests passed.\n";
}
