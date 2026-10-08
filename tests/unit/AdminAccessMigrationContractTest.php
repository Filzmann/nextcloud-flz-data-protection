<?php

declare(strict_types=1);

$migration = (string)file_get_contents(dirname(__DIR__, 2) . '/lib/Migration/Version000002Date202608250001.php');
foreach (['flz_dp_admin_access', 'target_uid', 'granted_by', 'starts_at', 'ends_at', 'revoked_at', 'revoked_by', 'created_at', 'flz_dp_admin_target_time', 'flz_dp_admin_grantor_time'] as $contract) {
    if (!str_contains($migration, $contract)) throw new RuntimeException('Additiver Adminfreigabe-Migrationsvertrag fehlt: ' . $contract);
}

echo "Data Protection admin access migration contract passed.\n";
