<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Service;

use OCP\IGroupManager;
use OCP\IUserSession;

final class RetentionAccessService {
    public function __construct(
        private IUserSession $session,
        private IGroupManager $groups,
        private RetentionSettingsService $settings,
        private TemporaryAdminAccessChecker $temporaryAdminAccess,
    ) {}

    public function canReview(): bool {
        $uid = $this->session->getUser()?->getUID();
        if ($uid === null || trim($uid) === '') return false;
        if ($this->groups->isAdmin($uid) && $this->temporaryAdminAccess->hasActiveGrant($uid)) return true;
        foreach ($this->settings->reviewerGroups() as $groupId) {
            if ($this->groups->isInGroup($uid, $groupId)) return true;
        }
        return false;
    }

    public function canConfigure(): bool {
        $uid = $this->session->getUser()?->getUID();
        return $uid !== null && trim($uid) !== '' && $this->groups->isAdmin($uid);
    }
}
