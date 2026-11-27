<?php

declare(strict_types=1);

use OCA\FlzDataProtection\Contract\OrganizationSnapshotSource;
use OCA\FlzDataProtection\Tests\Support\FakeL10N;
use OCA\FlzDataProtection\Controller\RetentionSettingsController;
use OCA\FlzDataProtection\Service\DataProtectionPermissionChecker;
use OCA\FlzDataProtection\Service\OrganizationGroupPolicy;
use OCA\FlzDataProtection\Service\PermissionAssignmentsService;
use OCP\AppFramework\Http\Http;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IRequest;

$config = new class implements IAppConfig {
    public array $values = [];
    public function getValueArray(string $appId, string $key, array $default = [], bool $lazy = false): array { return $this->values[$key] ?? $default; }
    public function getValueBool(string $appId, string $key, bool $default = false, bool $lazy = false): bool { return $default; }
    public function setValueArray(string $appId, string $key, array $value, bool $lazy = false): void { $this->values[$key] = $value; }
    public function setValueBool(string $appId, string $key, bool $value, bool $lazy = false): void {}
};
$groups = new class implements IGroupManager {
    public function isAdmin(string $uid): bool { return false; }
    public function isInGroup(string $uid, string $gid): bool { return false; }
    public function groupExists(string $gid): bool { return in_array($gid, ['Datenschutzbeauftragte', 'Org-Datenschutz', 'Fremdgruppe'], true); }
};
$source = new class implements OrganizationSnapshotSource {
    public function snapshot(): ?array { return [
        'contractVersion' => '1.0', 'valid' => true,
        'roles' => ['privacy' => ['groupId' => 'Org-Datenschutz', 'label' => 'Datenschutzrolle']],
        'areas' => [],
    ]; }
};
$settings = new PermissionAssignmentsService($config, $groups, new OrganizationGroupPolicy($source, $groups));
$authorization = new class implements DataProtectionPermissionChecker {
    public array $allowed = [];
    public function has(string $permission): bool { return in_array($permission, $this->allowed, true); }
};
$request = new class implements IRequest {
    public array $params = [];
    public function getParams(): array { return $this->params; }
};
$controller = new RetentionSettingsController($request, $authorization, $settings, new FakeL10N());

$request->params = ['assignments' => [PermissionAssignmentsService::RETENTION_REVIEW => ['Org-Datenschutz']]];
$before = $config->values;
if ($controller->save()->getStatus() !== Http::STATUS_FORBIDDEN || $config->values !== $before) {
    throw new RuntimeException('Ein Konto ohne Matrixrecht konnte eine Rechtezuordnung ändern.');
}

$authorization->allowed = [PermissionAssignmentsService::REVIEW_ASSIGNMENTS_CONFIGURE];
if ($controller->save()->getStatus() !== Http::STATUS_OK
    || $settings->groupsFor(PermissionAssignmentsService::RETENTION_REVIEW) !== ['Org-Datenschutz']) {
    throw new RuntimeException('Das getrennte Recht zur REVIEW-Rollenzuordnung kann REVIEW nicht konfigurieren.');
}
$request->params = ['assignments' => [
    PermissionAssignmentsService::RETENTION_REVIEW => ['Datenschutzbeauftragte'],
    PermissionAssignmentsService::RISK_SCOPE_CONFIGURE => ['Org-Datenschutz'],
]];
$before = $config->values;
if ($controller->save()->getStatus() !== Http::STATUS_FORBIDDEN || $config->values !== $before) {
    throw new RuntimeException('Das REVIEW-Rollenzuordnungsrecht konnte ein fremdes Einzelrecht manipulieren.');
}

$authorization->allowed = [PermissionAssignmentsService::PERMISSION_ASSIGNMENTS_CONFIGURE];
$request->params = ['assignments' => [PermissionAssignmentsService::RISK_SCOPE_CONFIGURE => ['Org-Datenschutz']]];
if ($controller->save()->getStatus() !== Http::STATUS_OK
    || $settings->groupsFor(PermissionAssignmentsService::RISK_SCOPE_CONFIGURE) !== ['Org-Datenschutz']) {
    throw new RuntimeException('Die autorisierte Rechtematrixverwaltung konnte ein Einzelrecht nicht zuordnen.');
}
$request->params = ['assignments' => [PermissionAssignmentsService::RISK_SCOPE_CONFIGURE => ['Fremdgruppe']]];
$before = $config->values;
if ($controller->save()->getStatus() !== Http::STATUS_BAD_REQUEST || $config->values !== $before) {
    throw new RuntimeException('Eine fachfremde Gruppe wurde akzeptiert oder hat eine Teiländerung hinterlassen.');
}

echo "Data Protection permission assignment controller tests passed.\n";
