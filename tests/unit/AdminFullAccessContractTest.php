<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$technicalAdminTemplate = (string)file_get_contents($root . '/templates/admin.php');
$template = (string)file_get_contents($root . '/templates/index.php');
$script = (string)file_get_contents($root . '/js/admin-access.js');
$routes = (string)file_get_contents($root . '/appinfo/routes.php');
$pageController = (string)file_get_contents($root . '/lib/Controller/PageController.php');

foreach (['data-protection-full-access-form', 'data-protection-full-access-enabled', 'data-protection-full-access-history', 'value="1440"'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException('App-lokale DPO-Freigabesteuerung fehlt: ' . $contract);
}
foreach (['/api/v1/admin/full-access', 'durationMinutes', 'targetUid', 'Widerrufen'] as $contract) {
    if (!str_contains($script . $routes, $contract)) throw new RuntimeException('Vollzugriffs-UI/API-Vertrag fehlt: ' . $contract);
}
if (!str_contains($routes, "'verb' => 'DELETE'")) throw new RuntimeException('Widerrufroute fehlt.');
if (str_contains($template . $script, 'allow_nextcloud_admin_review')) throw new RuntimeException('Unbefristeter nativer Adminzugriff ist noch aktivierbar.');
if (str_contains($technicalAdminTemplate, 'data-protection-full-access-form')) throw new RuntimeException('Freigabesteuerung ist noch an den technischen Adminbereich gebunden.');
foreach (['canManageAdminAccess', 'showMissingAdminGrant', 'showAdminAccessLink'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException('Rollenabhängige Eintritts- und UI-Grenze fehlt: ' . $contract);
    if (!str_contains($pageController, "'" . $contract . "'")) throw new RuntimeException('PageController liefert die Eintritts- und UI-Grenze nicht: ' . $contract);
}
if (!str_contains($template, 'Native Nextcloud-Administration erteilt keinen fachlichen Vollzugriff')) throw new RuntimeException('Sichere Eintrittsmeldung fehlt.');

echo "Data Protection admin full access UI contract passed.\n";
