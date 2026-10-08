<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\AppInfo;

use OCA\FlzDataProtection\Db\TemporaryAdminAccessRepository;
use OCA\FlzDataProtection\Db\TemporaryAdminAccessRepositoryInterface;
use OCA\FlzDataProtection\Db\ExecutableTemporaryAdminAccessRepositoryInterface;
use OCA\FlzDataProtection\Db\RetentionExecutionProfileRepository;
use OCA\FlzDataProtection\Db\RetentionExecutionProfileRepositoryInterface;
use OCA\FlzDataProtection\Db\RetentionExecutionActivationRepository;
use OCA\FlzDataProtection\Db\RetentionExecutionActivationRepositoryInterface;
use OCA\FlzDataProtection\Db\RiskScopeAuthorizationRepository;
use OCA\FlzDataProtection\Db\RiskScopeAuthorizationRepositoryInterface;
use OCA\FlzDataProtection\Db\RetentionHoldRepository;
use OCA\FlzDataProtection\Db\RetentionHoldRepositoryInterface;
use OCA\FlzDataProtection\Listener\ScopeAuthorizationQueryListener;
use OCA\FlzDataProtection\Permission\DataProtectionPermissionProviderListener;
use OCA\FlzDataProtection\Privacy\DataProtectionPersonalDataProviderListener;
use OCA\FlzDataProtection\Privacy\DataProtectionProcessingMetadataProviderListener;
use OCA\FlzDataProtection\Privacy\AdminHistoryRetentionProviderListener;
use OCA\FlzDataProtection\Privacy\AdminHistoryRetentionExecutionProviderListener;
use OCA\FlzDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCA\FlzDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
use OCA\FlzDataProtection\PublicApi\V1\RegisterRetentionProvidersEvent;
use OCA\FlzDataProtection\PublicApi\V1\ScopeAuthorizationQueryEvent;
use OCA\FlzDataProtection\PublicApi\V2\RegisterRetentionExecutionProvidersEvent;
use OCA\FlzDataProtection\Service\TemporaryAdminAccessChecker;
use OCA\FlzDataProtection\Service\TemporaryAdminAccessService;
use OCA\FlzDataProtection\Service\RetentionExecutionActivationService;
use OCA\FlzDataProtection\Service\RetentionExecutionProfileStatus;
use OCA\FlzPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

final class Application extends App implements IBootstrap {
    public const APP_ID = 'flz_data_protection';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(RegisterPersonalDataProvidersEvent::class, DataProtectionPersonalDataProviderListener::class);
        $context->registerEventListener(RegisterProcessingMetadataProvidersEvent::class, DataProtectionProcessingMetadataProviderListener::class);
        $context->registerEventListener(RegisterRetentionProvidersEvent::class, AdminHistoryRetentionProviderListener::class);
        $context->registerEventListener(RegisterRetentionExecutionProvidersEvent::class, AdminHistoryRetentionExecutionProviderListener::class);
        $context->registerEventListener(ScopeAuthorizationQueryEvent::class, ScopeAuthorizationQueryListener::class);
        $context->registerEventListener(RegisterPermissionProvidersEvent::class, DataProtectionPermissionProviderListener::class);
        $context->registerServiceAlias(TemporaryAdminAccessChecker::class, TemporaryAdminAccessService::class);
        $context->registerServiceAlias(TemporaryAdminAccessRepositoryInterface::class, TemporaryAdminAccessRepository::class);
        $context->registerServiceAlias(ExecutableTemporaryAdminAccessRepositoryInterface::class, TemporaryAdminAccessRepository::class);
        $context->registerServiceAlias(RetentionExecutionProfileRepositoryInterface::class, RetentionExecutionProfileRepository::class);
        $context->registerServiceAlias(RetentionExecutionActivationRepositoryInterface::class, RetentionExecutionActivationRepository::class);
        $context->registerServiceAlias(RetentionExecutionProfileStatus::class, RetentionExecutionActivationService::class);
        $context->registerServiceAlias(RiskScopeAuthorizationRepositoryInterface::class, RiskScopeAuthorizationRepository::class);
        $context->registerServiceAlias(RetentionHoldRepositoryInterface::class, RetentionHoldRepository::class);
    }

    public function boot(IBootContext $context): void {
    }
}
