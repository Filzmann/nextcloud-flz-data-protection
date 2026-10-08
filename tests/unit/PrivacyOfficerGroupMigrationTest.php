<?php

declare(strict_types=1);

use OCA\FlzDataProtection\Migration\Version000003Date202609250001;
use OCP\IGroupManager;
use OCP\Migration\IOutput;

$assertSame = static function (mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
};

$output = new class implements IOutput {
    public array $infoMessages = [];
    public function debug(string $message): void {}
    public function info($message): void { $this->infoMessages[] = $message; }
    public function warning($message): void {}
    public function startProgress($max = 0): void {}
    public function advance($step = 1, $description = ''): void {}
    public function finishProgress(): void {}
};

$newManager = static function (bool $exists = false, bool $creationAvailable = true): IGroupManager {
    return new class($exists, $creationAvailable) implements IGroupManager {
        public array $groups;
        public array $memberships = ['existing-user' => ['existing-group']];
        public int $createCalls = 0;

        public function __construct(bool $exists, private bool $creationAvailable) {
            $this->groups = $exists ? ['Datenschutzbeauftragte'] : [];
        }

        public function isAdmin(string $uid): bool { return false; }
        public function isInGroup(string $uid, string $gid): bool { return in_array($gid, $this->memberships[$uid] ?? [], true); }
        public function groupExists(string $gid): bool { return in_array($gid, $this->groups, true); }

        public function createGroup(string $gid): ?object {
            $this->createCalls++;
            if (!$this->creationAvailable) {
                return null;
            }
            if (!$this->groupExists($gid)) {
                $this->groups[] = $gid;
            }
            return new stdClass();
        }
    };
};

$schema = static fn(): never => throw new RuntimeException('The group migration must not inspect or modify the database schema.');

$missing = $newManager();
$migration = new Version000003Date202609250001($missing);
$migration->postSchemaChange($output, $schema, []);
$migration->postSchemaChange($output, $schema, []);
$assertSame(['Datenschutzbeauftragte'], $missing->groups, 'A missing canonical group must be created exactly once.');
$assertSame(1, $missing->createCalls, 'Repeated execution must not attempt to duplicate the canonical group.');
$assertSame(['existing-user' => ['existing-group']], $missing->memberships, 'Creating the canonical group must not grant membership or broaden access.');

$existing = $newManager(true);
(new Version000003Date202609250001($existing))->postSchemaChange($output, $schema, []);
$assertSame(['Datenschutzbeauftragte'], $existing->groups, 'An existing canonical group must remain unchanged.');
$assertSame(0, $existing->createCalls, 'An existing canonical group must not be recreated.');
$assertSame(['existing-user' => ['existing-group']], $existing->memberships, 'An existing group must not have its membership mutated.');

$unavailable = $newManager(false, false);
$failed = false;
try {
    (new Version000003Date202609250001($unavailable))->postSchemaChange($output, $schema, []);
} catch (RuntimeException $e) {
    $failed = str_contains($e->getMessage(), 'Datenschutzbeauftragte');
}
$assertSame(true, $failed, 'Unavailable native group creation must fail the migration with a diagnosable error.');
$assertSame([], $unavailable->groups, 'Failed group creation must not leave an app-local replacement group.');
$assertSame(['existing-user' => ['existing-group']], $unavailable->memberships, 'Failed group creation must not grant membership or broaden access.');

$info = (string)file_get_contents(dirname(__DIR__, 2) . '/appinfo/info.xml');
if (preg_match('/<version>([^<]+)<\/version>/', $info, $versionMatch) !== 1
    || version_compare($versionMatch[1], '0.1.2', '<')) {
    throw new RuntimeException('The app version must advance so already enabled installations execute the new migration on their next app upgrade.');
}

$application = (string)file_get_contents(dirname(__DIR__, 2) . '/lib/AppInfo/Application.php');
if (str_contains($application, 'createGroup(') || str_contains($application, 'Datenschutzbeauftragte')) {
    throw new RuntimeException('Canonical group provisioning must remain in the versioned install/upgrade lifecycle, not ordinary request bootstrap.');
}

echo "Data Protection privacy officer group migration tests passed.\n";
