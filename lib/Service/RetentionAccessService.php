<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Service;

use OCP\IGroupManager;
use OCP\IUserSession;

final class RetentionAccessService {
    public function __construct(
        private IUserSession $session,
        private IGroupManager $groups,
        private DataProtectionPermissionChecker $authorization,
        private TemporaryAdminAccessChecker $temporaryAdminAccess,
    ) {}

    public function canReview(): bool {
        $uid = $this->session->getUser()?->getUID();
        if ($uid === null || trim($uid) === '') return false;
        if ($this->authorization->has(PermissionAssignmentsService::RETENTION_REVIEW)) return true;
        return $this->groups->isAdmin($uid) && $this->temporaryAdminAccess->hasActiveGrant($uid);
    }

    public function canConfigure(): bool {
        return $this->authorization->has(PermissionAssignmentsService::PERMISSION_ASSIGNMENTS_CONFIGURE)
            || $this->authorization->has(PermissionAssignmentsService::REVIEW_ASSIGNMENTS_CONFIGURE);
    }
}
