<?php

declare(strict_types=1);

$application = (string)file_get_contents(dirname(__DIR__, 2) . '/lib/AppInfo/Application.php');
foreach (['RegisterPersonalDataProvidersEvent', 'DataProtectionPersonalDataProviderListener', 'RegisterProcessingMetadataProvidersEvent', 'DataProtectionProcessingMetadataProviderListener', 'RegisterPermissionProvidersEvent', 'DataProtectionPermissionProviderListener', 'ScopeAuthorizationQueryEvent', 'ScopeAuthorizationQueryListener', 'TemporaryAdminAccessChecker', 'TemporaryAdminAccessService', 'TemporaryAdminAccessRepositoryInterface', 'TemporaryAdminAccessRepository', 'ExecutableTemporaryAdminAccessRepositoryInterface', 'RetentionExecutionProfileRepositoryInterface', 'RetentionExecutionProfileRepository', 'RetentionExecutionActivationRepositoryInterface', 'RetentionExecutionActivationRepository', 'RetentionExecutionProfileStatus', 'RetentionExecutionActivationService', 'RiskScopeAuthorizationRepositoryInterface', 'RiskScopeAuthorizationRepository'] as $contract) {
    if (!str_contains($application, $contract)) throw new RuntimeException('Bootstrap-Registrierung fehlt: ' . $contract);
}

$info = simplexml_load_file(dirname(__DIR__, 2) . '/appinfo/info.xml');
if ($info === false) throw new RuntimeException('App-Metadaten sind ungültig.');
$jobs = array_map(static fn(SimpleXMLElement $job): string => (string)$job, $info->xpath('background-jobs/job') ?: []);
if (!in_array(OCA\FilzmannDataProtection\BackgroundJob\RetentionExecutionJob::class, $jobs, true)) {
    throw new RuntimeException('Der V2-Retention-Job ist für Fresh Install nicht nativ registriert.');
}

echo "Data Protection application registration contract passed.\n";
