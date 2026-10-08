<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$migration = (string)file_get_contents($root . '/lib/Migration/Version000004Date202609280001.php');
foreach ([
    'flz_dp_retention_profile', 'revision', 'profile_id', 'profile_revision',
    'legal_evidence_ref', 'scope_reference', 'effective_at', 'legal_review_due_at',
    'backup_regular_days', 'backup_buffer_days', 'backup_evidence_ref',
    'backup_evidence_at', 'backup_review_due_at', 'restore_test_ref',
    'restore_tested_at', 'dpo_confirmed', 'changed_by', 'created_at',
    'flz_dp_ret_prof_revision',
] as $contract) {
    if (!str_contains($migration, $contract)) {
        throw new RuntimeException('Additiver DP-07-Profilmigrationsvertrag fehlt: ' . $contract);
    }
}
if (preg_match('/drop(Table|Column|Index)|rename(Table|Column|Index)/i', $migration) === 1) {
    throw new RuntimeException('Die DP-07-Konfigurationsmigration darf bestehendes Schema nicht destruktiv verändern.');
}

$info = (string)file_get_contents($root . '/appinfo/info.xml');
if (preg_match('/<version>([^<]+)<\/version>/', $info, $versionMatch) !== 1
    || version_compare($versionMatch[1], '0.1.3', '<')) {
    throw new RuntimeException('Die App-Version muss für die additive DP-07-Konfigurationsmigration erhöht werden.');
}

echo "Retention execution profile migration contract passed.\n";
