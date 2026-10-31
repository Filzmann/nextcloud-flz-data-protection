<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$adminSettings = (string)file_get_contents($root . '/lib/Settings/Admin.php');
$assignments = (string)file_get_contents($root . '/lib/Service/PermissionAssignmentsService.php');

if (is_file($root . '/lib/Service/RetentionSettingsService.php')) {
    throw new RuntimeException('Die alte REVIEW-Gruppenkonfiguration konkurriert weiterhin mit der kanonischen Einzelrechtsmatrix.');
}
if (str_contains($adminSettings, 'RetentionSettingsService') || str_contains($adminSettings, "'settings'")) {
    throw new RuntimeException('Der technische Nextcloud-Adminbereich liest weiterhin fachliche REVIEW-Gruppeneinstellungen.');
}
if (!str_contains($assignments, "private const CONFIG_KEY = 'permission_assignments_v1'")) {
    throw new RuntimeException('Der kanonische app-lokale Konfigurationsschlüssel der Einzelrechtsmatrix fehlt.');
}

echo "Data Protection permission configuration source contract passed.\n";
