<?php

declare(strict_types=1);

use OCA\FlzDataProtection\Contract\OrganizationSnapshotSource;
use OCA\FlzDataProtection\Service\OrganizationGroupPolicy;
use OCP\IGroupManager;

$groups = new class implements IGroupManager {
    public function isAdmin(string $uid): bool { return false; }
    public function isInGroup(string $uid, string $gid): bool { return false; }
    public function groupExists(string $gid): bool {
        return in_array($gid, ['Datenschutzbeauftragte', 'Org-Gruppe', 'Vorhandene Fremdgruppe'], true);
    }
};

$source = static fn(?array $snapshot): OrganizationSnapshotSource => new class($snapshot) implements OrganizationSnapshotSource {
    public function __construct(private ?array $snapshot) {}
    public function snapshot(): ?array { return $this->snapshot; }
};

$fallback = new OrganizationGroupPolicy($source(null), $groups);
if (!$fallback->isAllowed('Datenschutzbeauftragte')) {
    throw new RuntimeException('Die kanonische Datenschutzgruppe ist ohne optionalen Organigrammprovider nicht nutzbar.');
}
if ($fallback->isAllowed('Vorhandene Fremdgruppe')) {
    throw new RuntimeException('Eine beliebige vorhandene Gruppe wird ohne Organigrammprovider akzeptiert.');
}

$withOrganization = new OrganizationGroupPolicy($source([
    'contractVersion' => '1.0',
    'valid' => true,
    'roles' => ['review' => ['groupId' => 'Org-Gruppe', 'label' => 'Prüfung']],
    'areas' => [],
]), $groups);
if (!$withOrganization->isAllowed('Datenschutzbeauftragte') || !$withOrganization->isAllowed('Org-Gruppe')) {
    throw new RuntimeException('Standard- und Organigrammgruppe werden nicht gemeinsam zugelassen.');
}
if ($withOrganization->isAllowed('Vorhandene Fremdgruppe')) {
    throw new RuntimeException('Eine fachfremde Gruppe wird neben dem Organigramm akzeptiert.');
}

$invalidOrganization = new OrganizationGroupPolicy($source([
    'contractVersion' => '2.0',
    'valid' => true,
    'roles' => ['review' => ['groupId' => 'Org-Gruppe', 'label' => 'Prüfung']],
    'areas' => [],
]), $groups);
if (!$invalidOrganization->isAllowed('Datenschutzbeauftragte') || $invalidOrganization->isAllowed('Org-Gruppe')) {
    throw new RuntimeException('Ein inkompatibler Organigrammvertrag wird nicht auf die feste Standardgruppe begrenzt.');
}

$incompleteOrganization = new OrganizationGroupPolicy($source([
    'contractVersion' => '1.0',
    'valid' => true,
    'roles' => ['review' => ['groupId' => 'Org-Gruppe', 'label' => 'Prüfung']],
]), $groups);
if (!$incompleteOrganization->isAllowed('Datenschutzbeauftragte') || $incompleteOrganization->isAllowed('Org-Gruppe')) {
    throw new RuntimeException('Ein unvollständiger Organigrammvertrag lässt bereits gelesene Teilgruppen zu.');
}

echo "Data Protection organization group policy tests passed.\n";
