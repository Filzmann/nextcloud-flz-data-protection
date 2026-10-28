<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$info = (string)file_get_contents($root . '/appinfo/info.xml');
$routes = (string)file_get_contents($root . '/appinfo/routes.php');
$pageController = (string)file_get_contents($root . '/lib/Controller/PageController.php');
$selfServiceController = (string)file_get_contents($root . '/lib/Controller/SelfServiceController.php');
$appTemplate = (string)file_get_contents($root . '/templates/index.php');

foreach ([
    'lib/Settings/Personal.php',
    'lib/Settings/PersonalSection.php',
    'templates/personal.php',
] as $relativePath) {
    if (!is_file($root . '/' . $relativePath)) {
        throw new RuntimeException('Persönlicher Datenschutz-Einstieg fehlt: ' . $relativePath);
    }
}

$personalSettings = (string)file_get_contents($root . '/lib/Settings/Personal.php');
$personalSection = (string)file_get_contents($root . '/lib/Settings/PersonalSection.php');
$personalTemplate = (string)file_get_contents($root . '/templates/personal.php');

foreach ([
    '<personal>OCA\FlzDataProtection\Settings\Personal</personal>',
    '<personal-section>OCA\FlzDataProtection\Settings\PersonalSection</personal-section>',
] as $registration) {
    if (!str_contains($info, $registration)) {
        throw new RuntimeException('Native Registrierung der persönlichen Datenschutz-Einstellung fehlt: ' . $registration);
    }
}
if (!str_contains($personalSettings, "'personal'")) throw new RuntimeException('Persönliche Einstellung rendert nicht das getrennte Self-Service-Template.');
if (!str_contains($personalSettings, 'return Application::APP_ID')) throw new RuntimeException('Persönliche Einstellung und Abschnitt verwenden nicht dieselbe kanonische ID.');
if (!str_contains($personalSection, "return 'Datenschutz'")) throw new RuntimeException('Persönlicher Nextcloud-Abschnitt heißt nicht Datenschutz.');

foreach (['id="data-protection-personal"', 'id="data-protection-results"', 'Persönliche Auskunft'] as $contract) {
    if (!str_contains($personalTemplate, $contract)) throw new RuntimeException('Self-Service-Template ist unvollständig: ' . $contract);
}
foreach (['data-protection-full-access-form', 'data-protection-retention-policy-form', 'data-protection-retention-results', 'data-protection-risk-scope-authorization-form'] as $forbidden) {
    if (str_contains($personalTemplate, $forbidden)) throw new RuntimeException('Fachliche DPO-Steuerung ist im persönlichen Self-Service sichtbar: ' . $forbidden);
}
if (str_contains($appTemplate, 'id="data-protection-results"')) throw new RuntimeException('Art.-15-Self-Service ist weiterhin über das App-Menü erreichbar.');

if (!str_contains($routes, "'name' => 'page#index'")) throw new RuntimeException('Direktzugriff auf den fachlichen App-Einstieg fehlt.');
if (!preg_match('/#\[NoAdminRequired\]\s+#\[NoCSRFRequired\]\s+public function index\(\): TemplateResponse/', $pageController) || str_contains($pageController, 'PublicPage')) {
    throw new RuntimeException('Der App-Direktzugriff muss authentifiziert, aber nicht auf native Admins begrenzt sein.');
}
foreach (['canReviewRetention', 'canManageAdminAccess', 'canConfigureAdminHistoryRetention', 'canConfigureRetentionExecutionProfile', 'canConfigureRetentionExecutionActivation', 'canConfigureRiskScopeAuthorizations'] as $serverSideBoundary) {
    if (!str_contains($pageController, "'" . $serverSideBoundary . "'")) {
        throw new RuntimeException('Bestehende serverseitige App-Grenze fehlt: ' . $serverSideBoundary);
    }
}
if (!preg_match('/#\[NoAdminRequired\]\s+#\[NoCSRFRequired\]\s+public function report\(\): JSONResponse/', $selfServiceController)) {
    throw new RuntimeException('Sessiongebundener Self-Service-Direktzugriff ist nicht als authentifizierter Lesezugriff deklariert.');
}
if (str_contains($selfServiceController, 'PublicPage') || !preg_match('/function report\(\): JSONResponse/', $selfServiceController)) {
    throw new RuntimeException('Self-Service darf weder anonym noch mit einer frei übermittelten Ziel-UID erreichbar sein.');
}

if (!function_exists('script')) {
    function script(...$arguments): void {}
}
if (!function_exists('style')) {
    function style(...$arguments): void {}
}
$renderApp = static function (array $parameters) use ($root): string {
    $_ = $parameters;
    ob_start();
    require $root . '/templates/index.php';
    return (string)ob_get_clean();
};
$deniedOutput = $renderApp([
    'canReviewRetention' => false,
    'canManageAdminAccess' => false,
    'showMissingAdminGrant' => false,
    'showAdminAccessLink' => false,
    'canConfigureAdminHistoryRetention' => false,
    'canConfigureRetentionExecutionProfile' => false,
    'canConfigureRetentionExecutionActivation' => false,
    'canConfigureRiskScopeAuthorizations' => false,
    'retentionExecutionProfileSetupRequired' => false,
]);
foreach (['data-protection-full-access-form', 'data-protection-retention-policy-form', 'data-protection-retention-execution-activation-form', 'data-protection-risk-scope-authorization-form', 'data-protection-retention-results'] as $protectedControl) {
    if (str_contains($deniedOutput, $protectedControl)) {
        throw new RuntimeException('Unberechtigter App-Direktzugriff rendert geschützte Steuerung: ' . $protectedControl);
    }
}
if (!str_contains($deniedOutput, 'Kein fachlicher Bereich verfügbar')) {
    throw new RuntimeException('Unberechtigter App-Direktzugriff erhält keinen sicheren Leerzustand.');
}

$allowedOutput = $renderApp([
    'canReviewRetention' => true,
    'canManageAdminAccess' => true,
    'showMissingAdminGrant' => false,
    'showAdminAccessLink' => false,
    'canConfigureAdminHistoryRetention' => true,
    'canConfigureRetentionExecutionProfile' => true,
    'canConfigureRetentionExecutionActivation' => true,
    'canConfigureRiskScopeAuthorizations' => true,
    'retentionExecutionProfileSetupRequired' => false,
]);
foreach (['data-protection-governance', 'data-protection-technical-operations', 'data-protection-review-area'] as $allowedArea) {
    if (!str_contains($allowedOutput, 'id="' . $allowedArea . '"')) {
        throw new RuntimeException('Berechtigter App-Direktzugriff rendert den freigegebenen Bereich nicht: ' . $allowedArea);
    }
}

echo "Data Protection personal settings/navigation contract passed.\n";
