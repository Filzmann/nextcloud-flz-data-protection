<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Migration;

use OCA\FilzmannDataProtection\Service\TemporaryAdminAccessService;
use Closure;
use OCP\IGroupManager;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use RuntimeException;

final class Version000003Date202609250001 extends SimpleMigrationStep {
    public function __construct(private IGroupManager $groups) {
    }

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        if ($this->groups->groupExists(TemporaryAdminAccessService::GRANT_MANAGER_GROUP)) {
            $output->info('Die Nextcloud-Gruppe Datenschutzbeauftragte ist bereits vorhanden.');
            return;
        }

        if ($this->groups->createGroup(TemporaryAdminAccessService::GRANT_MANAGER_GROUP) === null) {
            throw new RuntimeException('Die erforderliche Nextcloud-Gruppe Datenschutzbeauftragte konnte nicht angelegt werden.');
        }

        $output->info('Die Nextcloud-Gruppe Datenschutzbeauftragte wurde angelegt.');
    }
}
