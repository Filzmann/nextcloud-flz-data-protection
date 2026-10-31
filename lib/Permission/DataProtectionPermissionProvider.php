<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Permission;

use OCA\FlzDataProtection\Service\PermissionAssignmentsService;
use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionCondition;
use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionProvider;
use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionProviderDescriptor;
use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionProviderResult;
use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionRule;

final class DataProtectionPermissionProvider implements PermissionProvider {
    public function __construct(private PermissionAssignmentsService $settings) {
    }

    public function descriptor(): PermissionProviderDescriptor {
        return new PermissionProviderDescriptor(
            'flz_data_protection',
            'Datenschutz-Center',
            '1.0',
            ['permissions'],
        );
    }

    public function collect(): PermissionProviderResult {
        $temporaryAdmin = PermissionCondition::all([
            PermissionCondition::nextcloudAdmin(),
            PermissionCondition::temporaryAppAdminGrant(),
        ]);
        $conditionFor = fn(string $permission): PermissionCondition => PermissionCondition::any(array_map(
            static fn(string $groupId): PermissionCondition => PermissionCondition::group($groupId),
            $this->settings->groupsFor($permission),
        ));
        $reviewConditions = array_map(
            static fn(string $groupId): PermissionCondition => PermissionCondition::group($groupId),
            $this->settings->groupsFor(PermissionAssignmentsService::RETENTION_REVIEW),
        );
        $reviewConditions[] = $temporaryAdmin;

        return new PermissionProviderResult([
            new PermissionRule(
                'AppPermission',
                'Datenschutz-Selbstauskunft',
                'Nur für die eigene authentifizierte Nextcloud-Kennung',
                'personal-data.self',
                'Eigene Auskunft lesen',
                'allow',
                'app:flz_data_protection',
                PermissionCondition::all([PermissionCondition::authenticated(), PermissionCondition::self()]),
                'flz_data_protection:SelfServiceController::report',
                'high',
            ),
            new PermissionRule(
                'AppPermission',
                'Retention-REVIEW',
                'Konfigurierte Prüfgruppen oder native Administration mit aktiver app-lokaler Freigabe',
                'retention.review',
                'Retention prüfen',
                'allow',
                'app:flz_data_protection',
                PermissionCondition::any($reviewConditions),
                'flz_data_protection:RetentionAccessService::canReview',
                'high',
            ),
            new PermissionRule(
                'AppPermission',
                'Rechts- und Backup-Profil konfigurieren',
                'App-lokal zugewiesene Organigrammrollen; Standard Datenschutzbeauftragte',
                'retention.execution-profile.configure',
                'Rechts- und Backup-Profil konfigurieren',
                'allow',
                'app:flz_data_protection',
                $conditionFor(PermissionAssignmentsService::EXECUTION_PROFILE_CONFIGURE),
                'flz_data_protection:RetentionExecutionProfileService::canConfigure',
                'high',
            ),
            new PermissionRule(
                'AppPermission',
                'Automatische Retention technisch aktivieren',
                'Ausschließlich Nextcloud-native Administration; erteilt keinen fachlichen Datenzugriff',
                'retention.execution.configure',
                'Automatische Retention technisch aktivieren',
                'allow',
                'app:flz_data_protection',
                PermissionCondition::nextcloudAdmin(),
                'flz_data_protection:RetentionExecutionActivationService::canConfigure',
                'high',
            ),
            new PermissionRule(
                'AppPermission',
                'Risikoscope konfigurieren',
                'App-lokal zugewiesene Organigrammrollen; Standard Datenschutzbeauftragte',
                'risk-scope.configure',
                'Risikoscope konfigurieren',
                'allow',
                'app:flz_data_protection',
                $conditionFor(PermissionAssignmentsService::RISK_SCOPE_CONFIGURE),
                'flz_data_protection:RiskScopeAuthorizationService::canConfigure',
                'high',
            ),
            new PermissionRule('AppPermission', 'REVIEW-Rollen konfigurieren', 'App-lokal zugewiesene Organigrammrollen', PermissionAssignmentsService::REVIEW_ASSIGNMENTS_CONFIGURE, 'REVIEW-Rollen konfigurieren', 'allow', 'app:flz_data_protection', $conditionFor(PermissionAssignmentsService::REVIEW_ASSIGNMENTS_CONFIGURE), 'flz_data_protection:RetentionSettingsController::save', 'high'),
            new PermissionRule('AppPermission', 'Adminhistorien-Retention konfigurieren', 'App-lokal zugewiesene Organigrammrollen', PermissionAssignmentsService::ADMIN_HISTORY_RETENTION_CONFIGURE, 'Retention und Prüfprotokoll pflegen', 'allow', 'app:flz_data_protection', $conditionFor(PermissionAssignmentsService::ADMIN_HISTORY_RETENTION_CONFIGURE), 'flz_data_protection:AdminHistoryRetentionPolicyService::canConfigure', 'high'),
            new PermissionRule('AppPermission', 'Temporäre Adminfreigaben verwalten', 'App-lokal zugewiesene Organigrammrollen', PermissionAssignmentsService::TEMPORARY_ADMIN_ACCESS_MANAGE, 'Adminfreigaben erteilen und widerrufen', 'allow', 'app:flz_data_protection', $conditionFor(PermissionAssignmentsService::TEMPORARY_ADMIN_ACCESS_MANAGE), 'flz_data_protection:TemporaryAdminAccessService::canManageGrants', 'high'),
            new PermissionRule('AppPermission', 'Adminfreigabehistorie lesen', 'App-lokal zugewiesene Organigrammrollen', PermissionAssignmentsService::TEMPORARY_ADMIN_ACCESS_HISTORY_READ, 'Adminfreigabehistorie lesen', 'allow', 'app:flz_data_protection', $conditionFor(PermissionAssignmentsService::TEMPORARY_ADMIN_ACCESS_HISTORY_READ), 'flz_data_protection:TemporaryAdminAccessService::state', 'high'),
            new PermissionRule('AppPermission', 'Datenschutz-Rechtematrix verwalten', 'App-lokal zugewiesene Organigrammrollen', PermissionAssignmentsService::PERMISSION_ASSIGNMENTS_CONFIGURE, 'Datenschutz-Rechtematrix verwalten', 'allow', 'app:flz_data_protection', $conditionFor(PermissionAssignmentsService::PERMISSION_ASSIGNMENTS_CONFIGURE), 'flz_data_protection:RetentionSettingsController::save', 'high'),
        ]);
    }
}
