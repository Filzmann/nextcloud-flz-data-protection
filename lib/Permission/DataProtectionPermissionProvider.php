<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Permission;

use OCA\FilzmannDataProtection\Service\RetentionSettingsService;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\PermissionCondition;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\PermissionProvider;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\PermissionProviderDescriptor;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\PermissionProviderResult;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\PermissionRule;

final class DataProtectionPermissionProvider implements PermissionProvider {
    public function __construct(private RetentionSettingsService $settings) {
    }

    public function descriptor(): PermissionProviderDescriptor {
        return new PermissionProviderDescriptor(
            'filzmann_data_protection',
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
        $reviewConditions = array_map(
            static fn(string $groupId): PermissionCondition => PermissionCondition::group($groupId),
            $this->settings->reviewerGroups(),
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
                'app:filzmann_data_protection',
                PermissionCondition::all([PermissionCondition::authenticated(), PermissionCondition::self()]),
                'filzmann_data_protection:SelfServiceController::report',
                'high',
            ),
            new PermissionRule(
                'AppPermission',
                'Retention-REVIEW',
                'Konfigurierte Prüfgruppen oder native Administration mit aktiver app-lokaler Freigabe',
                'retention.review',
                'Retention prüfen',
                'allow',
                'app:filzmann_data_protection',
                PermissionCondition::any($reviewConditions),
                'filzmann_data_protection:RetentionAccessService::canReview',
                'high',
            ),
            new PermissionRule(
                'AppPermission',
                'Technische Datenschutz-Konfiguration',
                'Nextcloud-native Administration; erteilt keinen fachlichen REVIEW-Zugriff',
                'retention.configure',
                'Technische Konfiguration verwalten',
                'allow',
                'app:filzmann_data_protection',
                PermissionCondition::nextcloudAdmin(),
                'filzmann_data_protection:RetentionAccessService::canConfigure',
                'high',
            ),
        ]);
    }
}
