<?php

declare(strict_types=1);

use OCA\FlzDataProtection\Service\RetentionAccessService;
use OCA\FlzDataProtection\Service\TemporaryAdminAccessService;

return [
    'providerRegistrations' => [
        'flz_data_protection' => [
            OCA\FlzDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent::class,
            OCA\FlzDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent::class,
            OCA\FlzDataProtection\PublicApi\V1\RegisterRetentionProvidersEvent::class,
        ],
        'flz_permission_matrix' => [
            OCA\FlzPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent::class,
        ],
    ],
    'uiPath' => '/index.php/apps/flz_data_protection/',
    'preGrantUiStatuses' => [200],
    'postGrantUiStatuses' => [200],
    'grantService' => TemporaryAdminAccessService::class,
    'grantManagerGroups' => static fn(): array => [TemporaryAdminAccessService::GRANT_MANAGER_GROUP],
    'permissionProbe' => static fn(string $uid): bool => OCP\Server::get(RetentionAccessService::class)
        ->canReview(),
    'apiSmokes' => [
        ['/index.php/apps/flz_data_protection/api/v1/self-service-report', [200]],
        ['/index.php/apps/flz_data_protection/api/v1/retention-review', [200]],
    ],
];
