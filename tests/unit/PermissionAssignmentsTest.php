<?php

declare(strict_types=1);

use OCA\FlzDataProtection\Contract\OrganizationSnapshotSource;
use OCA\FlzDataProtection\Service\DataProtectionAuthorizationService;
use OCA\FlzDataProtection\Service\OrganizationGroupPolicy;
use OCA\FlzDataProtection\Service\PermissionAssignmentsService;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

$assertSame = static function (mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Erwartet: ' . var_export($expected, true) . '; erhalten: ' . var_export($actual, true));
    }
};

$config = new class implements IAppConfig {
    public array $values = [];
    public function getValueArray(string $appId, string $key, array $default = [], bool $lazy = false): array { return $this->values[$key] ?? $default; }
    public function getValueBool(string $appId, string $key, bool $default = false, bool $lazy = false): bool { return $default; }
    public function setValueArray(string $appId, string $key, array $value, bool $lazy = false): void { $this->values[$key] = $value; }
    public function setValueBool(string $appId, string $key, bool $value, bool $lazy = false): void {}
};
$groups = new class implements IGroupManager {
    public array $memberships = [
        'dpo' => ['Datenschutzbeauftragte'],
        'org-reviewer' => ['Org-Datenschutz'],
    ];
    public array $admins = ['native-admin'];
    public bool $failMembership = false;
    public function isAdmin(string $uid): bool { return in_array($uid, $this->admins, true); }
    public function isInGroup(string $uid, string $gid): bool {
        if ($this->failMembership) throw new RuntimeException('group backend unavailable');
        return in_array($gid, $this->memberships[$uid] ?? [], true);
    }
    public function groupExists(string $gid): bool { return in_array($gid, ['Datenschutzbeauftragte', 'Org-Datenschutz', 'Fremdgruppe'], true); }
};
$source = new class implements OrganizationSnapshotSource {
    public function snapshot(): ?array {
        return [
            'contractVersion' => '1.0',
            'valid' => true,
            'roles' => ['data-protection' => ['groupId' => 'Org-Datenschutz', 'label' => 'Datenschutzrolle']],
            'areas' => [],
        ];
    }
};
$assignments = new PermissionAssignmentsService($config, $groups, new OrganizationGroupPolicy($source, $groups));

$expectedPermissions = [
    PermissionAssignmentsService::RETENTION_REVIEW,
    PermissionAssignmentsService::REVIEW_ASSIGNMENTS_CONFIGURE,
    PermissionAssignmentsService::EXECUTION_PROFILE_CONFIGURE,
    PermissionAssignmentsService::RISK_SCOPE_CONFIGURE,
    PermissionAssignmentsService::ADMIN_HISTORY_RETENTION_CONFIGURE,
    PermissionAssignmentsService::TEMPORARY_ADMIN_ACCESS_MANAGE,
    PermissionAssignmentsService::TEMPORARY_ADMIN_ACCESS_HISTORY_READ,
    PermissionAssignmentsService::PERMISSION_ASSIGNMENTS_CONFIGURE,
];
$assertSame($expectedPermissions, array_keys($assignments->assignments()), 'Das Datenschutz-Rechteinventar ist nicht vollständig oder nicht stabil sortiert.');
foreach ($assignments->assignments() as $permission => $roleGroups) {
    $assertSame(['Datenschutzbeauftragte'], $roleGroups, 'Das Recht ' . $permission . ' besitzt nicht den freigegebenen Datenschutz-Default.');
}

$saved = $assignments->save([
    PermissionAssignmentsService::RETENTION_REVIEW => ['Org-Datenschutz'],
]);
$assertSame(['Org-Datenschutz'], $saved[PermissionAssignmentsService::RETENTION_REVIEW], 'Eine Organigrammrolle wurde nicht dem einzelnen REVIEW-Recht zugeordnet.');
$assertSame(['Datenschutzbeauftragte'], $saved[PermissionAssignmentsService::RISK_SCOPE_CONFIGURE], 'Das Speichern eines Rechts hat ein anderes Recht verändert.');

$beforeEmptySave = $config->values;
try {
    $assignments->save([PermissionAssignmentsService::RETENTION_REVIEW => []]);
    throw new RuntimeException('Ein Datenschutzrecht wurde ohne berechtigte Rolle gespeichert.');
} catch (InvalidArgumentException) {
}
$assertSame($beforeEmptySave, $config->values, 'Eine abgewiesene leere Rechtezuordnung hat die Konfiguration verändert.');

$beforeRejectedSave = $config->values;
try {
    $assignments->save([
        PermissionAssignmentsService::RETENTION_REVIEW => ['Fremdgruppe'],
        PermissionAssignmentsService::RISK_SCOPE_CONFIGURE => ['Org-Datenschutz'],
    ]);
    throw new RuntimeException('Eine vorhandene, aber fachfremde Gruppe wurde als Berechtigungsrolle akzeptiert.');
} catch (InvalidArgumentException) {
}
$assertSame($beforeRejectedSave, $config->values, 'Eine abgewiesene Zuordnung hat eine Teiländerung hinterlassen.');

try {
    $assignments->save(['retention.execution.configure' => ['Datenschutzbeauftragte']]);
    throw new RuntimeException('Das native technische Adminrecht wurde app-lokal konfigurierbar gemacht.');
} catch (InvalidArgumentException) {
}

$session = new class implements IUserSession {
    public ?string $uid = null;
    public function getUser(): ?IUser {
        if ($this->uid === null) return null;
        return new class($this->uid) implements IUser {
            public function __construct(private string $uid) {}
            public function getUID(): string { return $this->uid; }
        };
    }
};
$logger = new class implements LoggerInterface {
    public array $errors = [];
    public function emergency(string|Stringable $message, array $context = []): void {}
    public function alert(string|Stringable $message, array $context = []): void {}
    public function critical(string|Stringable $message, array $context = []): void {}
    public function error(string|Stringable $message, array $context = []): void { $this->errors[] = [(string)$message, $context]; }
    public function warning(string|Stringable $message, array $context = []): void {}
    public function notice(string|Stringable $message, array $context = []): void {}
    public function info(string|Stringable $message, array $context = []): void {}
    public function debug(string|Stringable $message, array $context = []): void {}
    public function log(mixed $level, string|Stringable $message, array $context = []): void {}
};
$authorization = new DataProtectionAuthorizationService($session, $groups, $assignments, $logger);

$session->uid = 'dpo';
$assertSame(false, $authorization->has(PermissionAssignmentsService::RETENTION_REVIEW), 'Nach expliziter Umbelegung behält die Defaultgruppe unzulässig REVIEW-Zugriff.');
$assertSame(true, $authorization->has(PermissionAssignmentsService::RISK_SCOPE_CONFIGURE), 'Die Default-Datenschutzrolle verliert ein nicht umbelegtes Einzelrecht.');
$session->uid = 'org-reviewer';
$assertSame(true, $authorization->has(PermissionAssignmentsService::RETENTION_REVIEW), 'Die explizit zugeordnete Organigrammrolle erhält ihr Recht nicht.');
$assertSame(false, $authorization->has(PermissionAssignmentsService::RISK_SCOPE_CONFIGURE), 'Die REVIEW-Rolle erhält versehentlich ein anderes Datenschutzrecht.');
$session->uid = 'native-admin';
$assertSame(false, $authorization->has(PermissionAssignmentsService::RETENTION_REVIEW), 'Nativer Adminstatus erteilt fachliches REVIEW ohne Freigabe.');
$assertSame(false, $authorization->has(PermissionAssignmentsService::PERMISSION_ASSIGNMENTS_CONFIGURE), 'Nativer Adminstatus erteilt die fachliche Rechteverwaltung.');
$groups->failMembership = true;
$session->uid = 'dpo';
$assertSame(false, $authorization->has(PermissionAssignmentsService::RISK_SCOPE_CONFIGURE), 'Fehler der Gruppenprüfung bleiben nicht fail-closed.');
$assertSame(true, count($logger->errors) > 0, 'Die fail-closed Gruppenprüfung bleibt nicht datensparsam diagnostizierbar.');

echo "Data Protection permission assignment tests passed.\n";
