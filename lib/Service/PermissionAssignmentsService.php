<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Service;

use InvalidArgumentException;
use OCA\FlzDataProtection\AppInfo\Application;
use OCP\IAppConfig;
use OCP\IGroupManager;

final class PermissionAssignmentsService {
    public const RETENTION_REVIEW = 'retention.review';
    public const REVIEW_ASSIGNMENTS_CONFIGURE = 'retention.review-assignments.configure';
    public const EXECUTION_PROFILE_CONFIGURE = 'retention.execution-profile.configure';
    public const RISK_SCOPE_CONFIGURE = 'risk-scope.configure';
    public const ADMIN_HISTORY_RETENTION_CONFIGURE = 'admin-history-retention.configure';
    public const TEMPORARY_ADMIN_ACCESS_MANAGE = 'temporary-admin-access.manage';
    public const TEMPORARY_ADMIN_ACCESS_HISTORY_READ = 'temporary-admin-access.history.read';
    public const PERMISSION_ASSIGNMENTS_CONFIGURE = 'permissions.configure';

    private const CONFIG_KEY = 'permission_assignments_v1';
    private const DEFAULT_GROUP = 'Datenschutzbeauftragte';

    /** @var array<string,string> */
    private const DEFINITIONS = [
        self::RETENTION_REVIEW => 'Retention-REVIEW lesen',
        self::REVIEW_ASSIGNMENTS_CONFIGURE => 'REVIEW-Rollen konfigurieren',
        self::EXECUTION_PROFILE_CONFIGURE => 'Rechts- und Backup-Profil pflegen',
        self::RISK_SCOPE_CONFIGURE => 'Risikoscopes konfigurieren',
        self::ADMIN_HISTORY_RETENTION_CONFIGURE => 'Retention und Prüfprotokoll der Adminfreigaben pflegen',
        self::TEMPORARY_ADMIN_ACCESS_MANAGE => 'Temporäre Adminfreigaben erteilen und widerrufen',
        self::TEMPORARY_ADMIN_ACCESS_HISTORY_READ => 'Adminfreigabehistorie lesen',
        self::PERMISSION_ASSIGNMENTS_CONFIGURE => 'Datenschutz-Rechtematrix verwalten',
    ];

    public function __construct(
        private IAppConfig $config,
        private IGroupManager $groups,
        private OrganizationGroupPolicy $organizationGroups,
    ) {}

    /** @return array<string,string> */
    public function definitions(): array {
        return self::DEFINITIONS;
    }

    public function isKnown(string $permission): bool {
        return array_key_exists($permission, self::DEFINITIONS);
    }

    /** @return array<string,list<string>> */
    public function assignments(): array {
        $stored = $this->config->getValueArray(Application::APP_ID, self::CONFIG_KEY, [], true);
        $fresh = $stored === [];
        $result = [];
        foreach (self::DEFINITIONS as $permission => $_label) {
            $candidate = $fresh || !array_key_exists($permission, $stored)
                ? [self::DEFAULT_GROUP]
                : $stored[$permission];
            $result[$permission] = is_array($candidate)
                ? $this->normalizeReadableGroups($candidate)
                : [];
        }
        return $result;
    }

    /** @return list<string> */
    public function groupsFor(string $permission): array {
        if (!$this->isKnown($permission)) return [];
        return $this->assignments()[$permission];
    }

    /**
     * @param array<string,mixed> $changes
     * @return array<string,list<string>>
     */
    public function save(array $changes): array {
        if ($changes === []) {
            throw new InvalidArgumentException('Mindestens eine Rechtezuordnung ist erforderlich.');
        }

        $next = $this->assignments();
        foreach ($changes as $permission => $groups) {
            if (!is_string($permission) || !$this->isKnown($permission)) {
                throw new InvalidArgumentException('Unbekanntes Datenschutzrecht.');
            }
            if (!is_array($groups)) {
                throw new InvalidArgumentException('Die Rollenzuordnung ist ungültig.');
            }
            $normalizedGroups = $this->normalizeWritableGroups($groups);
            if ($normalizedGroups === []) {
                throw new InvalidArgumentException('Jedes Datenschutzrecht benötigt mindestens eine berechtigte Rolle.');
            }
            $next[$permission] = $normalizedGroups;
        }
        if ($next[self::PERMISSION_ASSIGNMENTS_CONFIGURE] === []) {
            throw new InvalidArgumentException('Die Verwaltung der Datenschutz-Rechtematrix benötigt mindestens eine berechtigte Rolle.');
        }

        $this->config->setValueArray(Application::APP_ID, self::CONFIG_KEY, $next, true);
        return $this->assignments();
    }

    /** @return array{definitions:array<string,string>,assignments:array<string,list<string>>,organization_groups:array<string,mixed>,unavailable_groups:list<string>} */
    public function state(): array {
        $organizationState = $this->organizationGroups->state();
        $assignments = $this->assignments();
        $available = array_column($organizationState['options'], 'groupId');
        $configured = [];
        foreach ($assignments as $groups) {
            $configured = [...$configured, ...$groups];
        }
        return [
            'definitions' => self::DEFINITIONS,
            'assignments' => $assignments,
            'organization_groups' => $organizationState,
            'unavailable_groups' => array_values(array_diff(array_values(array_unique($configured)), $available)),
        ];
    }

    /** @param array<mixed> $groups @return list<string> */
    private function normalizeReadableGroups(array $groups): array {
        $normalized = [];
        foreach ($groups as $groupId) {
            if (!is_string($groupId)) continue;
            $groupId = trim($groupId);
            if ($groupId === '' || in_array($groupId, $normalized, true)) continue;
            if (!$this->organizationGroups->isAllowed($groupId)) continue;
            $normalized[] = $groupId;
        }
        sort($normalized, SORT_NATURAL | SORT_FLAG_CASE);
        return $normalized;
    }

    /** @param array<mixed> $groups @return list<string> */
    private function normalizeWritableGroups(array $groups): array {
        $normalized = [];
        foreach ($groups as $groupId) {
            if (!is_string($groupId)) {
                throw new InvalidArgumentException('Eine Rollenzuordnung ist ungültig.');
            }
            $groupId = trim($groupId);
            if ($groupId === '' || in_array($groupId, $normalized, true)) continue;
            if (!$this->organizationGroups->isAllowed($groupId) || !$this->groups->groupExists($groupId)) {
                throw new InvalidArgumentException('Die Rolle „' . $groupId . '“ muss aus dem aktuellen Organigramm gewählt werden und in Nextcloud existieren.');
            }
            $normalized[] = $groupId;
        }
        sort($normalized, SORT_NATURAL | SORT_FLAG_CASE);
        return $normalized;
    }
}
