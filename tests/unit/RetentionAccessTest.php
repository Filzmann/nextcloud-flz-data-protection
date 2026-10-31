<?php

declare(strict_types=1);

use OCA\FlzDataProtection\Service\DataProtectionPermissionChecker;
use OCA\FlzDataProtection\Service\PermissionAssignmentsService;
use OCA\FlzDataProtection\Service\RetentionAccessService;
use OCA\FlzDataProtection\Service\TemporaryAdminAccessChecker;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;

$assertSame = static function (mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) throw new RuntimeException($message);
};

$groups = new class implements IGroupManager {
    public array $admins = ['admin-user'];
    public function isAdmin(string $uid): bool { return in_array($uid, $this->admins, true); }
    public function isInGroup(string $uid, string $gid): bool { return false; }
    public function groupExists(string $gid): bool { return true; }
};
$sessionFor = static function (?string $uid): IUserSession {
    return new class($uid) implements IUserSession {
        public function __construct(private ?string $uid) {}
        public function getUser(): ?IUser {
            if ($this->uid === null) return null;
            return new class($this->uid) implements IUser {
                public function __construct(private string $uid) {}
                public function getUID(): string { return $this->uid; }
            };
        }
    };
};
$grants = new class implements TemporaryAdminAccessChecker {
    public array $active = [];
    public function hasActiveGrant(string $uid): bool { return in_array($uid, $this->active, true); }
};
$accessFor = static function (?string $uid) use ($sessionFor, $groups, $grants): RetentionAccessService {
    $checker = new class($uid) implements DataProtectionPermissionChecker {
        public function __construct(private ?string $uid) {}
        public function has(string $permission): bool {
            return $permission === PermissionAssignmentsService::RETENTION_REVIEW && $this->uid === 'privacy-user';
        }
    };
    return new RetentionAccessService($sessionFor($uid), $groups, $checker, $grants);
};

$assertSame(false, $accessFor('admin-user')->canReview(), 'Native Admins dürfen ohne app-lokale Freigabe kein REVIEW lesen.');
$grants->active = ['admin-user'];
$assertSame(true, $accessFor('admin-user')->canReview(), 'Eine aktive, UID-genaue Adminfreigabe muss REVIEW-Zugriff erteilen.');
$grants->active = [];
$assertSame(true, $accessFor('privacy-user')->canReview(), 'Die dem REVIEW-Recht zugeordnete Rolle erhält keinen Zugriff.');
$assertSame(false, $accessFor('ordinary-user')->canReview(), 'Ein gewöhnliches Konto erhält REVIEW-Zugriff.');
$assertSame(false, $accessFor(null)->canReview(), 'Anonyme Aufrufe erhalten REVIEW-Zugriff.');
$assertSame(false, $accessFor('admin-user')->canConfigure(), 'Nativer Adminstatus erteilt die fachliche Rechtekonfiguration.');

echo "Retention access tests passed.\n";
