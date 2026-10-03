<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$migration = (string)file_get_contents($root . '/lib/Migration/Version000006Date202609290002.php');
foreach ([
    'fdp_retention_exec', 'revision', 'enabled', 'approved_policy_ids',
    'backup_regular_days', 'backup_buffer_days', 'backup_verified_at',
    'restore_verified_at', 'verification_due_at', 'changed_by', 'created_at',
    'fdp_ret_exec_revision',
] as $contract) {
    if (!str_contains($migration, $contract)) {
        throw new RuntimeException('Technischer Aktivierungsmigrationsvertrag fehlt: ' . $contract);
    }
}
foreach (['legal_evidence', 'agreement', 'dpo_confirmed', 'authorization_ref'] as $forbidden) {
    if (str_contains($migration, $forbidden)) {
        throw new RuntimeException('Kundenlokale Rechts-/DPO-Evidenz ist Teil der technischen Aktivierungstabelle: ' . $forbidden);
    }
}
if (preg_match('/drop(Table|Column|Index)|rename(Table|Column|Index)/i', $migration) === 1) {
    throw new RuntimeException('Die technische Aktivierungsmigration darf kein bestehendes Schema destruktiv ändern.');
}

echo "Retention execution activation migration contract passed.\n";
