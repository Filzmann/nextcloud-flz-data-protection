<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\AppInfo;

use OCA\FilzmannDataProtection\Db\TemporaryAdminAccessRepository;
use OCA\FilzmannDataProtection\Db\TemporaryAdminAccessRepositoryInterface;
use OCA\FilzmannDataProtection\Db\RetentionExecutionProfileRepository;
use OCA\FilzmannDataProtection\Db\RetentionExecutionProfileRepositoryInterface;
use OCA\FilzmannDataProtection\Permission\DataProtectionPermissionProviderListener;
use OCA\FilzmannDataProtection\Privacy\DataProtectionPersonalDataProviderListener;
use OCA\FilzmannDataProtection\Privacy\DataProtectionProcessingMetadataProviderListener;
use OCA\FilzmannDataProtection\Privacy\AdminHistoryRetentionProviderListener;
use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCA\FilzmannDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
use OCA\FilzmannDataProtection\PublicApi\V1\RegisterRetentionProvidersEvent;
use OCA\FilzmannDataProtection\Service\TemporaryAdminAccessChecker;
use OCA\FilzmannDataProtection\Service\TemporaryAdminAccessService;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

final class Application extends App implements IBootstrap {
    public const APP_ID = 'filzmann_data_protection';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(RegisterPersonalDataProvidersEvent::class, DataProtectionPersonalDataProviderListener::class);
        $context->registerEventListener(RegisterProcessingMetadataProvidersEvent::class, DataProtectionProcessingMetadataProviderListener::class);
        $context->registerEventListener(RegisterRetentionProvidersEvent::class, AdminHistoryRetentionProviderListener::class);
        $context->registerEventListener(RegisterPermissionProvidersEvent::class, DataProtectionPermissionProviderListener::class);
        $context->registerServiceAlias(TemporaryAdminAccessChecker::class, TemporaryAdminAccessService::class);
        $context->registerServiceAlias(TemporaryAdminAccessRepositoryInterface::class, TemporaryAdminAccessRepository::class);
        $context->registerServiceAlias(RetentionExecutionProfileRepositoryInterface::class, RetentionExecutionProfileRepository::class);
    }

    public function boot(IBootContext $context): void {
    }
}
