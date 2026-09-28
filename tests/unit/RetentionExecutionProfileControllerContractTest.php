<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$routes = (string)file_get_contents($root . '/appinfo/routes.php');
$controller = (string)file_get_contents($root . '/lib/Controller/RetentionExecutionProfileController.php');

foreach (['retention_execution_profile#show', 'retention_execution_profile#save'] as $route) {
    if (!str_contains($routes, $route)) {
        throw new RuntimeException('DPO-geschützter Konfigurationsendpunkt fehlt: ' . $route);
    }
}
if (!str_contains($controller, '#[NoAdminRequired]') || !str_contains($controller, 'canConfigure()')) {
    throw new RuntimeException('Der Controller erzwingt die app-lokale DPO-Autorisierung nicht serverseitig.');
}
if (preg_match('/function\s+(execute|delete|run)\s*\(/i', $controller) === 1
    || preg_match("#/retention[^']*(execute|delete|run)#i", $routes) === 1) {
    throw new RuntimeException('Der Konfigurationsschritt darf keinen Retention-Ausführungs- oder Löschendpunkt einführen.');
}
if (!str_contains($controller, '#[NoCSRFRequired]') || substr_count($controller, '#[NoCSRFRequired]') !== 1) {
    throw new RuntimeException('Nur der lesende Profilendpunkt darf von der CSRF-Prüfung ausgenommen sein.');
}

echo "Retention execution profile controller contract passed.\n";
