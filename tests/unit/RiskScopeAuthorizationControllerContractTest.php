<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$routes = (string)file_get_contents($root . '/appinfo/routes.php');
$controllerPath = $root . '/lib/Controller/RiskScopeAuthorizationController.php';
if (!is_file($controllerPath)) {
    throw new RuntimeException('Der DPO-geschützte Scope-Konfigurationscontroller fehlt.');
}
$controller = (string)file_get_contents($controllerPath);

foreach (['risk_scope_authorization#show', 'risk_scope_authorization#save'] as $route) {
    if (!str_contains($routes, $route)) {
        throw new RuntimeException('DPO-geschützter Scope-Konfigurationsendpunkt fehlt: ' . $route);
    }
}
if (!str_contains($controller, '#[NoAdminRequired]') || !str_contains($controller, 'canConfigure()')) {
    throw new RuntimeException('Der Scope-Controller erzwingt die app-lokale DPO-Autorisierung nicht serverseitig.');
}
if (!str_contains($controller, '#[NoCSRFRequired]') || substr_count($controller, '#[NoCSRFRequired]') !== 1) {
    throw new RuntimeException('Nur der lesende Scope-Endpunkt darf von der CSRF-Prüfung ausgenommen sein.');
}
if (preg_match('/#\[NoCSRFRequired\]\s+public function save\(/', $controller) === 1) {
    throw new RuntimeException('Die Scope-Mutation ist nicht CSRF-geschützt.');
}
if (!str_contains($controller, 'catch (DomainException') || !str_contains($controller, 'Http::STATUS_CONFLICT')) {
    throw new RuntimeException('Ein konkurrierender Scope-Schreibvorgang wird nicht als HTTP 409 abgebildet.');
}

echo "Risk scope authorization controller contract passed.\n";
