<?php

declare(strict_types=1);

namespace OCP {
    interface IAppConfig {
        public function getValueArray(string $appId, string $key, array $default = [], bool $lazy = false): array;
        public function getValueBool(string $appId, string $key, bool $default = false, bool $lazy = false): bool;
        public function setValueArray(string $appId, string $key, array $value, bool $lazy = false): void;
        public function setValueBool(string $appId, string $key, bool $value, bool $lazy = false): void;
    }
    interface IGroupManager {
        public function isAdmin(string $uid): bool;
        public function isInGroup(string $uid, string $gid): bool;
        public function groupExists(string $gid): bool;
    }
}

namespace {
    use OCA\FilzmannDataProtection\Service\RetentionAccessService;
    use OCA\FilzmannDataProtection\Service\RetentionSettingsService;
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
    $assertSame(true, $settings->allowNextcloudAdmins(), 'Admins müssen nach Neuinstallation standardmäßig REVIEW lesen dürfen.');
    $assertSame(['Datenschutzbeauftragte'], $settings->reviewerGroups(), 'Die dedizierte Datenschutzgruppe muss standardmäßig vorgesehen sein.');
    $assertSame(true, (new RetentionAccessService($sessionFor('admin-user'), $groups, $settings))->canReview(), 'Native Admins verlieren den anfänglichen REVIEW-Zugriff.');
    $assertSame(true, (new RetentionAccessService($sessionFor('privacy-user'), $groups, $settings))->canReview(), 'Die konfigurierte Prüfgruppe erhält keinen REVIEW-Zugriff.');
    $assertSame(false, (new RetentionAccessService($sessionFor('ordinary-user'), $groups, $settings))->canReview(), 'Ein gewöhnliches Konto erhält REVIEW-Zugriff.');
    $assertSame(false, (new RetentionAccessService($sessionFor(null), $groups, $settings))->canReview(), 'Anonyme Aufrufe erhalten REVIEW-Zugriff.');

    $saved = $settings->save(['reviewer_groups' => ['IKT-Ausschuss'], 'allow_nextcloud_admin_review' => false]);
    $assertSame(false, $saved['allow_nextcloud_admin_review'], 'Das anfängliche Admin-Leserecht lässt sich nicht entziehen.');
    $assertSame(['IKT-Ausschuss'], $saved['reviewer_groups'], 'Die dedizierten Prüfgruppen sind nicht konfigurierbar.');
    $assertSame(false, (new RetentionAccessService($sessionFor('admin-user'), $groups, $settings))->canReview(), 'Ein deaktiviertes Admin-Leserecht bleibt wirksam.');
    $assertSame(true, (new RetentionAccessService($sessionFor('admin-user'), $groups, $settings))->canConfigure(), 'Admins müssen die technische Konfiguration trotz entzogenem fachlichem Leserecht verwalten können.');

    $writesBefore = $store->values;
    try {
        $settings->save(['reviewer_groups' => ['Unbekannte Gruppe'], 'allow_nextcloud_admin_review' => false]);
        throw new RuntimeException('Eine unbekannte Prüfgruppe wurde akzeptiert.');
    } catch (InvalidArgumentException) {
    }
    $assertSame($writesBefore, $store->values, 'Eine abgelehnte Konfiguration hat Teiländerungen hinterlassen.');

    echo "Retention access tests passed.\n";
}
