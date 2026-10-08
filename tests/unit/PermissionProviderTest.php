<?php

declare(strict_types=1);

namespace OCA\FlzPermissionMatrix\PublicApi\V1 {
    interface PermissionProvider { public function descriptor(): PermissionProviderDescriptor; public function collect(): PermissionProviderResult; }
    final class PermissionProviderDescriptor { public function __construct(public string $appId, public string $name, public string $version, public array $capabilities) {} }
    final class PermissionCondition {
        private function __construct(public string $operator, public ?string $groupId = null, public array $children = []) {}
        public static function authenticated(): self { return new self('authenticated'); }
        public static function self(): self { return new self('self'); }
        public static function nextcloudAdmin(): self { return new self('nextcloud-admin'); }
        public static function temporaryAppAdminGrant(): self { return new self('app-admin-grant'); }
        public static function group(string $groupId): self { return new self('group', $groupId); }
        public static function all(array $children): self { return new self('all', null, $children); }
        public static function any(array $children): self { return new self('any', null, $children); }
    }
    final class PermissionRule { public function __construct(public string $type, public string $name, public string $detail, public string $permission, public string $label, public string $effect, public string $scope, public PermissionCondition $condition, public string $source, public string $confidence) {} }
    final class PermissionProviderResult { public function __construct(public array $rules, public bool $complete = true, public array $warnings = []) {} }
    final class RegisterPermissionProvidersEvent extends \OCP\EventDispatcher\Event {
        public array $providers = [];
        public function register(PermissionProvider $provider): void { $this->providers[] = $provider; }
    }
}

namespace {
    use OCA\FlzDataProtection\Permission\DataProtectionPermissionProvider;
    use OCA\FlzDataProtection\Permission\DataProtectionPermissionProviderListener;
    use OCA\FlzDataProtection\Service\RetentionSettingsService;
    use OCA\FlzPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
    use OCP\IAppConfig;
    use OCP\IGroupManager;

    $config = new class implements IAppConfig {
        public function getValueArray(string $appId, string $key, array $default = [], bool $lazy = false): array { return ['Datenschutzbeauftragte']; }
        public function getValueBool(string $appId, string $key, bool $default = false, bool $lazy = false): bool { return $default; }
        public function setValueArray(string $appId, string $key, array $value, bool $lazy = false): void {}
        public function setValueBool(string $appId, string $key, bool $value, bool $lazy = false): void {}
    };
    $groups = new class implements IGroupManager {
        public function isAdmin(string $uid): bool { return false; }
        public function isInGroup(string $uid, string $gid): bool { return false; }
        public function groupExists(string $gid): bool { return true; }
    };
    $settings = new RetentionSettingsService($config, $groups);
    $provider = new DataProtectionPermissionProvider($settings);
    $rules = $provider->collect()->rules;
    $byPermission = [];
    foreach ($rules as $rule) $byPermission[$rule->permission] = $rule;

    if (array_keys($byPermission) !== ['personal-data.self', 'retention.review', 'retention.configure', 'retention.execution-profile.configure', 'retention.execution.configure', 'risk-scope.configure']) throw new \RuntimeException('Das Datenschutz-Rechteinventar ist unvollständig.');
    if (array_map(static fn($child): string => $child->operator, $byPermission['personal-data.self']->condition->children) !== ['authenticated', 'self']) throw new \RuntimeException('Selbstauskunft ist nicht strikt an Anmeldung und eigene Person gebunden.');
    $reviewChildren = $byPermission['retention.review']->condition->children;
    if ($reviewChildren[0]->operator !== 'group' || $reviewChildren[0]->groupId !== 'Datenschutzbeauftragte') throw new \RuntimeException('Konfigurierte Prüfgruppe fehlt.');
    $temporaryAdmin = $reviewChildren[1];
    if ($temporaryAdmin->operator !== 'all' || array_map(static fn($child): string => $child->operator, $temporaryAdmin->children) !== ['nextcloud-admin', 'app-admin-grant']) throw new \RuntimeException('Native Administration ist nicht an die aktive app-lokale Freigabe gekoppelt.');
    if ($byPermission['retention.configure']->condition->operator !== 'nextcloud-admin') throw new \RuntimeException('Technisches Konfigurationsrecht wurde mit fachlichem REVIEW vermischt.');
    $profileConfiguration = $byPermission['retention.execution-profile.configure'];
    if ($profileConfiguration->condition->operator !== 'group'
        || $profileConfiguration->condition->groupId !== 'Datenschutzbeauftragte'
        || $profileConfiguration->source !== 'flz_data_protection:RetentionExecutionProfileService::canConfigure') {
        throw new \RuntimeException('Die DPO-Profilkonfiguration fehlt oder ist nicht exakt an Datenschutzbeauftragte gebunden.');
    }
    $executionConfiguration = $byPermission['retention.execution.configure'];
    if ($executionConfiguration->condition->operator !== 'nextcloud-admin'
        || $executionConfiguration->source !== 'flz_data_protection:RetentionExecutionActivationService::canConfigure') {
        throw new \RuntimeException('Die technische Ausführungsaktivierung ist nicht exakt an native Administration gebunden.');
    }
    $scopeConfiguration = $byPermission['risk-scope.configure'];
    if ($scopeConfiguration->condition->operator !== 'group'
        || $scopeConfiguration->condition->groupId !== 'Datenschutzbeauftragte'
        || $scopeConfiguration->source !== 'flz_data_protection:RiskScopeAuthorizationService::canConfigure') {
        throw new \RuntimeException('Die Risikoscope-Konfiguration fehlt oder ist nicht exakt an Datenschutzbeauftragte gebunden.');
    }

    $event = new RegisterPermissionProvidersEvent();
    (new DataProtectionPermissionProviderListener($provider))->handle($event);
    if (($event->providers[0] ?? null) !== $provider) throw new \RuntimeException('Lazy PermissionProvider-Registrierung fehlt.');

    echo "Data Protection permission provider tests passed.\n";
}
