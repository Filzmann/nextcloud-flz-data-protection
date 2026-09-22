<?php

declare(strict_types=1);

$controller = (string)file_get_contents(dirname(__DIR__, 2) . '/lib/Controller/TemporaryAdminAccessController.php');

foreach (['function status()', 'function activate(', 'function revoke(', 'TemporaryAdminAccessDeniedException', 'STATUS_FORBIDDEN', 'STATUS_BAD_REQUEST', 'STATUS_INTERNAL_SERVER_ERROR'] as $contract) {
    if (!str_contains($controller, $contract)) throw new RuntimeException('Adminfreigabe-Controllervertrag fehlt: ' . $contract);
}
foreach (['status', 'activate', 'revoke'] as $method) {
    if (!preg_match('/#\[NoAdminRequired\](?:\s+#\[NoCSRFRequired\])?\s+public function ' . $method . '\(/', $controller)) {
        throw new RuntimeException('Datenschutzbeauftragte ohne nativen Adminstatus erreichen ' . $method . ' nicht.');
    }
}
if (!preg_match('/#\[NoAdminRequired\]\s+#\[NoCSRFRequired\]\s+public function status\(\)/', $controller)) throw new RuntimeException('Nur der read-only Status darf CSRF-frei sein.');
if (preg_match('/#\[NoCSRFRequired\]\s+public function (activate|revoke)\(/', $controller)) throw new RuntimeException('Schreibende Adminfreigabe wurde CSRF-frei geschaltet.');

echo "Data Protection temporary admin access controller contract passed.\n";
