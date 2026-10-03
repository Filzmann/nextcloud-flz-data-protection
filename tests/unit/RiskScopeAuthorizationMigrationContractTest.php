<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$migrationPath = $root . '/lib/Migration/Version000005Date202609290001.php';
if (!is_file($migrationPath)) {
    throw new RuntimeException('Die additive Risikoscope-Migration fehlt.');
}
$migration = (string)file_get_contents($migrationPath);
foreach ([
    'fdp_risk_scope_auth', 'scope_id', 'revision', 'schema_version', 'enabled',
    'policy_revision', 'authorization_ref', 'effective_at', 'expires_at',
    'dpo_confirmed', 'created_at', 'fdp_risk_scope_rev',
] as $contract) {
    if (!str_contains($migration, $contract)) {
        throw new RuntimeException('Risikoscope-Migrationsvertrag fehlt: ' . $contract);
    }
}
if (!str_contains($migration, "addUniqueIndex(['scope_id', 'revision']")) {
    throw new RuntimeException('Die Datenbank erzwingt keine atomar eindeutige Scope-Revision.');
}
if (preg_match('/drop(Table|Column|Index)|rename(Table|Column|Index)/i', $migration) === 1) {
    throw new RuntimeException('Die Risikoscope-Migration darf bestehendes Schema nicht destruktiv verändern.');
}

$info = (string)file_get_contents($root . '/appinfo/info.xml');
if (preg_match('/<version>([^<]+)<\/version>/', $info, $versionMatch) !== 1
    || version_compare($versionMatch[1], '0.1.4', '<')) {
    throw new RuntimeException('Die App-Version muss die additive Risikoscope-Migration enthalten.');
}

echo "Risk scope authorization migration contract passed.\n";
