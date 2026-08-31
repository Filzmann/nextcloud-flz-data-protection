<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$readOnlyActions = [
    'PageController.php' => 'index',
    'SelfServiceController.php' => 'report',
    'RetentionReviewController.php' => 'report',
];

foreach ($readOnlyActions as $file => $action) {
    $controller = (string)file_get_contents($root . '/lib/Controller/' . $file);
    $pattern = '/#\[NoAdminRequired\]\s+#\[NoCSRFRequired\]\s+public function ' . preg_quote($action, '/') . '\(/';
    if (!preg_match($pattern, $controller)) {
        throw new RuntimeException($file . '::' . $action . ' muss als authentifizierter, CSRF-freier Lesezugriff deklariert sein.');
    }
    if (str_contains($controller, 'PublicPage')) {
        throw new RuntimeException($file . ' darf durch die CSRF-Korrektur nicht anonym erreichbar werden.');
    }
}

$settings = (string)file_get_contents($root . '/lib/Controller/RetentionSettingsController.php');
if (preg_match('/#\[NoCSRFRequired\]\s+public function save\(/', $settings)) {
    throw new RuntimeException('Das Speichern der Retention-Einstellungen muss CSRF-geschützt bleiben.');
}

echo "Data Protection read-only CSRF contract passed.\n";
