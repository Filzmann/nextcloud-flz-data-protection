<?php

declare(strict_types=1);

$application = (string)file_get_contents(dirname(__DIR__, 2) . '/lib/AppInfo/Application.php');
foreach (['RegisterPersonalDataProvidersEvent', 'DataProtectionPersonalDataProviderListener', 'RegisterProcessingMetadataProvidersEvent', 'DataProtectionProcessingMetadataProviderListener', 'RegisterPermissionProvidersEvent', 'DataProtectionPermissionProviderListener', 'ScopeAuthorizationQueryEvent', 'ScopeAuthorizationQueryListener', 'TemporaryAdminAccessChecker', 'TemporaryAdminAccessService', 'TemporaryAdminAccessRepositoryInterface', 'TemporaryAdminAccessRepository', 'ExecutableTemporaryAdminAccessRepositoryInterface', 'RetentionExecutionProfileRepositoryInterface', 'RetentionExecutionProfileRepository', 'RetentionExecutionActivationRepositoryInterface', 'RetentionExecutionActivationRepository', 'RetentionExecutionProfileStatus', 'RetentionExecutionActivationService', 'RiskScopeAuthorizationRepositoryInterface', 'RiskScopeAuthorizationRepository'] as $contract) {
    if (!str_contains($application, $contract)) throw new RuntimeException('Bootstrap-Registrierung fehlt: ' . $contract);
}

echo "Data Protection application registration contract passed.\n";
