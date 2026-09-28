<?php

declare(strict_types=1);

use OCA\FilzmannDataProtection\Service\RetentionAccessService;
use OCA\FilzmannDataProtection\Service\TemporaryAdminAccessService;

return [
    'providerRegistrations' => [
        'filzmann_data_protection' => [
            OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent::class,
            OCA\FilzmannDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent::class,
            OCA\FilzmannDataProtection\PublicApi\V1\RegisterRetentionProvidersEvent::class,
        ],
        'filzmann_permission_matrix' => [
            OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent::class,
        ],
    ],
    'uiPath' => '/index.php/apps/filzmann_data_protection/',
    'preGrantUiStatuses' => [200],
    'postGrantUiStatuses' => [200],
    'grantService' => TemporaryAdminAccessService::class,
    'grantManagerGroups' => static fn(): array => [TemporaryAdminAccessService::GRANT_MANAGER_GROUP],
    'permissionProbe' => static fn(string $uid): bool => OCP\Server::get(RetentionAccessService::class)
        ->canReview(),
    'apiSmokes' => [
        ['/index.php/apps/filzmann_data_protection/api/v1/self-service-report', [200]],
        ['/index.php/apps/filzmann_data_protection/api/v1/retention-review', [200]],
    ],
];
