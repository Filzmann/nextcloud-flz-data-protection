<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Service;

use OCP\IGroupManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

final class DataProtectionAuthorizationService implements DataProtectionPermissionChecker {
    public function __construct(
        private IUserSession $session,
        private IGroupManager $groups,
        private PermissionAssignmentsService $assignments,
        private LoggerInterface $logger,
    ) {}

    public function has(string $permission): bool {
        if (!$this->assignments->isKnown($permission)) return false;
        $uid = $this->session->getUser()?->getUID() ?? '';
        if (trim($uid) === '') return false;

        try {
            foreach ($this->assignments->groupsFor($permission) as $groupId) {
                if ($this->groups->isInGroup($uid, $groupId)) return true;
            }
            return false;
        } catch (Throwable $error) {
            $this->logger->error('Datenschutz-Berechtigungsprüfung fehlgeschlagen.', [
                'permission' => $permission,
                'exception_class' => $error::class,
            ]);
            return false;
        }
    }
}
