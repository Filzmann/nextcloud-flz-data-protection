<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Service;

use OCA\FlzDataProtection\Contract\OrganizationSnapshotSource;
use OCA\FlzOrganization\PublicApi\V1\Contract as OrganizationContract;
use OCA\FlzOrganization\PublicApi\V1\OrganizationSnapshotService;
use OCP\App\IAppManager;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Throwable;

/** Liest ausschließlich den aktivierten öffentlichen Organisationsvertrag. */
final class FlzOrganizationSnapshotSource implements OrganizationSnapshotSource
{
    private const PROVIDER_APP_ID = 'flz_organization';
    private const CONTRACT_VERSION = '1.0';

    public function __construct(
        private IAppManager $apps,
        private LoggerInterface $logger,
    )
    {
    }

    public function snapshot(): ?array
    {
        try
        {
            // Vor Nextcloud 32 bezeichnet isInstalled() dieselbe instanzweite Aktivierung.
            $enabled = method_exists($this->apps, 'isEnabledForAnyone')
                ? $this->apps->isEnabledForAnyone(self::PROVIDER_APP_ID)
                : $this->apps->isInstalled(self::PROVIDER_APP_ID);
            if ($enabled !== true
                || !class_exists(OrganizationContract::class)
                || !method_exists(OrganizationContract::class, 'supports')
                || !OrganizationContract::supports(self::CONTRACT_VERSION)
                || !class_exists(OrganizationSnapshotService::class))
            {
                return null;
            }

            $provider = Server::get(OrganizationSnapshotService::class);
            if (!$provider instanceof OrganizationSnapshotService)
            {
                return null;
            }
            $snapshot = $provider->snapshot();
            if ($snapshot->contractVersion() !== self::CONTRACT_VERSION || $snapshot->isValid() !== true)
            {
                return null;
            }

            return [
                'contractVersion' => self::CONTRACT_VERSION,
                'valid' => true,
                'roles' => $snapshot->roles(),
                'areas' => $snapshot->areas(),
            ];
        } catch (Throwable $error)
        {
            $this->logger->warning('Datenschutz-Organisationsprovider ist nicht verfügbar.', [
                'provider' => self::PROVIDER_APP_ID,
                'exception_class' => $error::class,
            ]);
            return null;
        }
    }
}
