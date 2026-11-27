<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$technicalAdminTemplate = (string)file_get_contents($root . '/templates/admin.php');
$template = (string)file_get_contents($root . '/templates/index.php');
$script = (string)file_get_contents($root . '/js/admin-access.js');
$bundleOverlay = is_file($root . '/js/shared/overlay.js') ? (string)file_get_contents($root . '/js/shared/overlay.js') : '';
$routes = (string)file_get_contents($root . '/appinfo/routes.php');
$pageController = (string)file_get_contents($root . '/lib/Controller/PageController.php');

foreach (['data-protection-full-access-form', 'data-protection-full-access-enabled', 'data-protection-full-access-history', 'value="1440"'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException('App-lokale DPO-Freigabesteuerung fehlt: ' . $contract);
}
foreach (['/api/v1/admin/full-access', 'durationMinutes', 'targetUid'] as $contract) {
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

// Overlay-Vertrag: Das Warn-Icon am App-Titel ist ein Button, der ein modales Overlay öffnet.
$overlayId = 'data-protection-admin-access-overlay';
foreach ([
    'id="' . $overlayId . '"',
    'role="dialog"',
    'aria-modal="true"',
    'data-protection-admin-access-overlay-close',
    'id="data-protection-admin-grant-warning"',
    'aria-haspopup="dialog"',
    'aria-controls="' . $overlayId . '"',
    'aria-expanded',
] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException('Overlay-Vertrag fehlt in der Vorlage: ' . $contract);
}
if (str_contains($template, '<details class="data-protection-admin-grant-warning"')) throw new RuntimeException('Warn-Icon ist noch als aufklappbares details-Element umgesetzt.');
if (str_contains($template, 'href="#data-protection-full-access"')) throw new RuntimeException('Veralteter Anchor-Verweis auf die Freigabesteuerung ist noch vorhanden.');
$overlayPos = strpos($template, 'id="' . $overlayId . '"');
$sectionPos = strpos($template, 'data-protection-full-access-form');
$warningPos = strpos($template, 'Kein fachlicher Admin-Vollzugriff.');
if ($overlayPos === false || $sectionPos === false || $warningPos === false || $overlayPos >= $sectionPos || $overlayPos >= $warningPos) throw new RuntimeException('Warnung und Freigabesteuerung liegen nicht innerhalb des Overlays.');
if (!str_contains($script, $overlayId) || !str_contains($script, 'AdminAccessOverlay')) throw new RuntimeException('Overlay-Steuerung fehlt im JS: ' . $overlayId);
foreach (['AdminAccessOverlay', 'Overlay', 'Escape', 'aria-expanded', 'data-open-admin-access', 'flz-shared-overlay', 'flz-shared-warn-trigger', 'Widerrufen', 'hidden'] as $contract) {
    if (!str_contains($bundleOverlay, $contract)) throw new RuntimeException('Geteiltes Overlay-Bundle fehlt: ' . $contract);
}
foreach (["'shared/overlay'", 'class="flz-shared-overlay"', 'class="flz-shared-warn-trigger"'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException('Overlay-Vertrag fehlt in der Vorlage: ' . $contract);
}

echo "Data Protection admin full access overlay contract passed.\n";
