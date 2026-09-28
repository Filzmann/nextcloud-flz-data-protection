<?php

declare(strict_types=1);

$controller = (string)file_get_contents(dirname(__DIR__, 2) . '/lib/Controller/AdminHistoryRetentionPolicyController.php');

if (!str_contains($controller, 'use OCP\\AppFramework\\Http;')
    || str_contains($controller, 'use OCP\\AppFramework\\Http\\Http;')) {
    throw new RuntimeException('Der Retention-Policy-Controller verwendet nicht die echte Nextcloud-HTTP-Konstantenklasse.');
}
if (!preg_match('/#\[NoAdminRequired\]\s+#\[NoCSRFRequired\]\s+public function show\(\)/', $controller)) {
    throw new RuntimeException('Der read-only Policyabruf ist nicht getrennt von schreibenden CSRF-geschützten Routen deklariert.');
}
foreach (['save', 'review'] as $method) {
    if (!preg_match('/#\[NoAdminRequired\]\s+public function ' . $method . '\(/', $controller)
        || preg_match('/#\[NoCSRFRequired\]\s+public function ' . $method . '\(/', $controller)) {
        throw new RuntimeException('Schreibender Retention-Policy-Pfad ist nicht korrekt CSRF-geschützt: ' . $method);
    }
}

echo "Data Protection admin-history retention controller contract passed.\n";
