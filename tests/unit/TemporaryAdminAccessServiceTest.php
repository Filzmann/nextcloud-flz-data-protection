<?php

declare(strict_types=1);

use OCA\FilzmannDataProtection\Db\TemporaryAdminAccessRepositoryInterface;
use OCA\FilzmannDataProtection\Service\TemporaryAdminAccessDeniedException;
use OCA\FilzmannDataProtection\Service\TemporaryAdminAccessService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

$actor = new class implements IUser {
    public function getUID(): string { return 'admin-operator'; }
};
$session = new class($actor) implements IUserSession {
    public function __construct(public ?IUser $user) {}
    public function getUser(): ?IUser { return $this->user; }
};
$groups = new class implements IGroupManager {
    public array $admins = ['admin-operator', 'admin-target'];
    public function isAdmin(string $uid): bool { return in_array($uid, $this->admins, true); }
    public function isInGroup(string $uid, string $gid): bool { return false; }
    public function groupExists(string $gid): bool { return false; }
};
$clock = new class implements ITimeFactory {
    public function now(): DateTimeImmutable { return new DateTimeImmutable('2026-08-25T10:00:00+00:00'); }
};
$repository = new class implements TemporaryAdminAccessRepositoryInterface {
    public array $rows = [];
    public int $mutations = 0;
    public bool $failReads = false;
    public function replaceActive(string $targetUid, string $grantedBy, DateTimeImmutable $startsAt, DateTimeImmutable $endsAt): array {
        $this->mutations++;
        foreach ($this->rows as &$existing) {
            if ($existing['targetUid'] === $targetUid && $existing['revokedAt'] === null) $existing['revokedAt'] = $startsAt;
        }
        $row = ['id' => count($this->rows) + 1, 'targetUid' => $targetUid, 'grantedBy' => $grantedBy, 'startsAt' => $startsAt, 'endsAt' => $endsAt, 'revokedAt' => null, 'revokedBy' => null, 'createdAt' => $startsAt];
        $this->rows[] = $row;
        return $row;
    }
    public function revokeActive(string $targetUid, string $revokedBy, DateTimeImmutable $revokedAt): bool {
        foreach ($this->rows as &$row) {
            if ($row['targetUid'] === $targetUid && $row['revokedAt'] === null) {
                $row['revokedAt'] = $revokedAt;
                $row['revokedBy'] = $revokedBy;
                $this->mutations++;
                return true;
            }
        }
        return false;
    }
    public function activeFor(string $targetUid, DateTimeImmutable $at): ?array {
        if ($this->failReads) throw new RuntimeException('storage unavailable');
        foreach (array_reverse($this->rows) as $row) {
            if ($row['targetUid'] === $targetUid && $row['revokedAt'] === null && $row['startsAt'] <= $at && $row['endsAt'] > $at) return $row;
        }
        return null;
    }
    public function history(): array { return array_reverse($this->rows); }
    public function historyForUid(string $uid, int $limit, DateTimeImmutable $asOf): array { return []; }
};
$logger = new class implements LoggerInterface {
    public array $messages = [];
    public function emergency(string|Stringable $message, array $context = []): void {}
    public function alert(string|Stringable $message, array $context = []): void {}
    public function critical(string|Stringable $message, array $context = []): void {}
    public function error(string|Stringable $message, array $context = []): void { $this->messages[] = ['error', (string)$message, $context]; }
    public function warning(string|Stringable $message, array $context = []): void {}
    public function notice(string|Stringable $message, array $context = []): void {}
    public function info(string|Stringable $message, array $context = []): void { $this->messages[] = ['info', (string)$message, $context]; }
    public function debug(string|Stringable $message, array $context = []): void {}
    public function log(mixed $level, string|Stringable $message, array $context = []): void {}
};
$service = new TemporaryAdminAccessService($session, $groups, $repository, $clock, $logger);

try {
    $service->activate('admin-target', 1441);
    throw new RuntimeException('Mehr als 24 Stunden wurden akzeptiert.');
} catch (InvalidArgumentException) {
}
if ($repository->mutations !== 0) throw new RuntimeException('Eine ungültige Dauer darf keine Historie verändern.');

$grant = $service->activate('admin-target', 1440);
if ($grant['endsAt']->format(DATE_ATOM) !== '2026-08-26T10:00:00+00:00') throw new RuntimeException('Die 24-Stunden-Grenze ist fehlerhaft.');
if (!$service->hasActiveGrant('admin-target') || $service->hasActiveGrant('admin-other')) throw new RuntimeException('Die Freigabe ist nicht UID-genau.');

$groups->admins = ['admin-operator'];
if ($service->hasActiveGrant('admin-target')) throw new RuntimeException('Entzogener Nextcloud-Adminstatus muss die Freigabe sofort unwirksam machen.');
$groups->admins = ['admin-operator', 'admin-target'];
if (!$service->revoke('admin-target') || $service->hasActiveGrant('admin-target')) throw new RuntimeException('Widerruf muss den aktiven Zeitraum beenden.');

$repository->failReads = true;
if ($service->hasActiveGrant('admin-target')) throw new RuntimeException('Persistenzfehler muss fail-closed bleiben.');

$session->user = new class implements IUser { public function getUID(): string { return 'ordinary'; } };
$before = $repository->mutations;
try {
    $service->activate('admin-target', 60);
    throw new RuntimeException('Nicht-Admin durfte freigeben.');
} catch (TemporaryAdminAccessDeniedException) {
}
if ($repository->mutations !== $before) throw new RuntimeException('Abgewiesene Freigabe darf nichts persistieren.');

echo "Data Protection temporary admin access service tests passed.\n";
