<?php
script('flz_data_protection', ['retention-view', 'shared/overlay', 'admin-access', 'retention-policy', 'retention-execution-profile', 'risk-scope-authorization', 'admin', 'main']);
style('flz_data_protection', 'style');
$showSettings = ($_['canConfigureAdminHistoryRetention'] ?? false)
    || ($_['canConfigureRetentionExecutionProfile'] ?? false)
    || ($_['canConfigureRiskScopeAuthorizations'] ?? false)
    || ($_['canConfigurePermissionAssignments'] ?? false)
    || ($_['canConfigureReviewAssignments'] ?? false);
$showReview = $_['canReviewRetention'] ?? false;
$defaultTab = $showReview ? 'review' : 'settings';
$permissionSettings = is_array($_['permissionSettings'] ?? null) ? $_['permissionSettings'] : [];
$permissionDefinitions = is_array($permissionSettings['definitions'] ?? null) ? $permissionSettings['definitions'] : [];
$permissionAssignments = is_array($permissionSettings['assignments'] ?? null) ? $permissionSettings['assignments'] : [];
$organizationGroups = is_array($permissionSettings['organization_groups'] ?? null) ? $permissionSettings['organization_groups'] : [];
$groupOptions = is_array($organizationGroups['options'] ?? null) ? $organizationGroups['options'] : [];
?>

<main id="data-protection-app" aria-labelledby="data-protection-heading">
    <header class="data-protection-header">
        <div>
            <p class="data-protection-kicker">IKT und Datenschutz</p>
            <div class="data-protection-title-row"><h1 id="data-protection-heading">Datenschutz-Center</h1><?php if ($_['showMissingAdminGrant'] ?? false): ?><button type="button" id="data-protection-admin-grant-warning" class="flz-shared-warn-trigger" aria-haspopup="dialog" aria-expanded="false" aria-controls="data-protection-admin-access-overlay" aria-label="Fehlender fachlicher Admin-Vollzugriff: Freigabesteuerung im Overlay öffnen"><span aria-hidden="true">⚠</span></button><?php endif; ?></div>
        </div>
    </header>

    <?php if ($_['showMissingAdminGrant'] ?? false): ?>
        <section hidden class="data-protection-report data-protection-warning" aria-labelledby="data-protection-admin-access-required-heading">
            <h2 id="data-protection-admin-access-required-heading">Kein fachlicher Admin-Vollzugriff</h2>
            <p>Native Nextcloud-Administration erteilt keinen fachlichen Vollzugriff. Für den geschützten REVIEW-Bereich fehlt eine aktive app-lokale Freigabe.</p>
            <?php if ($_['showAdminAccessLink'] ?? false): ?>
                <p><button type="button" data-open-admin-access>Zur app-lokalen Freigabesteuerung</button></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($showSettings || $showReview): ?>
        <div class="data-protection-tablist" role="tablist" aria-label="Bereiche des Datenschutz-Centers">
            <?php if ($showReview): ?><button id="data-protection-review-tab" class="data-protection-tab" type="button" role="tab" aria-controls="data-protection-review-panel" aria-selected="<?php p($defaultTab === 'review' ? 'true' : 'false'); ?>" tabindex="<?php p($defaultTab === 'review' ? '0' : '-1'); ?>">REVIEW</button><?php endif; ?>
            <?php if ($showSettings): ?><button id="data-protection-settings-tab" class="data-protection-tab" type="button" role="tab" aria-controls="data-protection-settings-panel" aria-selected="<?php p($defaultTab === 'settings' ? 'true' : 'false'); ?>" tabindex="<?php p($defaultTab === 'settings' ? '0' : '-1'); ?>">Einstellungen</button><?php endif; ?>
        </div>
    <?php else: ?>
        <section class="data-protection-report data-protection-empty-state" aria-labelledby="data-protection-no-app-access-heading">
            <h2 id="data-protection-no-app-access-heading">Kein fachlicher Bereich verfügbar</h2>
            <p>Dieser App-Einstieg enthält ausschließlich geschützte Datenschutz- und Betriebsfunktionen. Die persönliche Art.-15-Auskunft befindet sich in den persönlichen Nextcloud-Einstellungen unter „Datenschutz“.</p>
        </section>
    <?php endif; ?>

    <?php if ($showSettings): ?>
        <section id="data-protection-settings-panel" class="data-protection-area" role="tabpanel" aria-labelledby="data-protection-settings-tab" <?php if ($defaultTab !== 'settings'): ?>hidden<?php endif; ?>>
            <h2>Datenschutz-Einstellungen</h2>
            <p>Diese Funktionen bleiben auf die jeweils serverseitig berechtigten Datenschutzrollen begrenzt.</p>
            <div class="data-protection-area-grid">

    <?php if (($_['canConfigurePermissionAssignments'] ?? false) || ($_['canConfigureReviewAssignments'] ?? false)): ?>
        <section class="data-protection-report data-protection-area-card" aria-labelledby="data-protection-permission-settings-heading">
            <h3 id="data-protection-permission-settings-heading">Datenschutz-Rechtematrix</h3>
            <p>Organigrammrollen und Rechte bleiben getrennt. Jede Zeile weist genau einem Recht die dafür vorgesehenen Rollen zu.</p>
            <form id="data-protection-permission-settings-form" data-manage-all="<?php p(($_['canConfigurePermissionAssignments'] ?? false) ? 'true' : 'false'); ?>">
                <?php foreach ($permissionDefinitions as $permission => $label): ?>
                    <?php $mayEdit = ($_['canConfigurePermissionAssignments'] ?? false) || (($_['canConfigureReviewAssignments'] ?? false) && $permission === 'retention.review'); ?>
                    <label>
                        <?php p((string)$label); ?>
                        <select data-permission="<?php p((string)$permission); ?>" multiple size="6" <?php if (!$mayEdit): ?>disabled<?php endif; ?>>
                            <?php foreach ($groupOptions as $option): ?>
                                <option value="<?php p((string)$option['groupId']); ?>" <?php if (in_array((string)$option['groupId'], $permissionAssignments[$permission] ?? [], true)): ?>selected<?php endif; ?> <?php if (!($option['exists'] ?? false)): ?>disabled<?php endif; ?>><?php p((string)$option['label']); ?> (<?php p((string)$option['kind']); ?>)<?php if (!($option['exists'] ?? false)): ?> – Gruppe fehlt in Nextcloud<?php endif; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endforeach; ?>
                <p>Jedes Recht benötigt mindestens eine Rolle. Mehrfachauswahl mit Strg/Befehlstaste. Die technische Retention-Aktivierung bleibt davon unabhängig und ausschließlich nativer Nextcloud-Administration vorbehalten.</p>
                <?php if (($organizationGroups['message'] ?? '') !== ''): ?><p role="alert"><?php p((string)$organizationGroups['message']); ?></p><?php endif; ?>
                <button type="submit">Rechtezuordnungen speichern</button>
            </form>
            <p id="data-protection-admin-notice" role="status" aria-live="polite" hidden></p>
        </section>
    <?php endif; ?>

    <?php if ($_['canConfigureAdminHistoryRetention'] ?? false): ?>
        <section id="data-protection-admin-history-retention" class="data-protection-report data-protection-area-card" aria-labelledby="data-protection-admin-history-retention-heading">
            <h3 id="data-protection-admin-history-retention-heading">Aufbewahrungsprüfung der Adminfreigabehistorie</h3>
            <p>Standard sind sechs Monate ab tatsächlichem Ende. Diese Konfiguration erzeugt ausschließlich REVIEW-Kandidaten und löscht keine Daten.</p>
            <form id="data-protection-retention-policy-form">
                <label>Kalenderfrist <input name="durationPeriod" value="P6M" pattern="P[1-9][0-9]*[YMD]" required></label>
                <input name="expectedRevision" type="hidden" value="0">
                <button type="submit">Frist als neue Version speichern</button>
                <button type="button" data-retention-review>Jährliche Prüfung protokollieren</button>
            </form>
            <p id="data-protection-retention-policy-status" role="status" aria-live="polite"></p>
        </section>
    <?php endif; ?>

    <?php if ($_['canConfigureRetentionExecutionProfile'] ?? false): ?>
        <section id="data-protection-retention-execution-profile" class="data-protection-report data-protection-area-card" aria-labelledby="data-protection-retention-execution-profile-heading">
            <h3 id="data-protection-retention-execution-profile-heading">Optionale lokale Rechts- und Betriebsdokumentation</h3>
            <p>Diese optionale Dokumentation erfasst kundeneigene Rechts- und Betriebsangaben. Sie aktiviert oder blockiert keine automatische Löschung; die technische Aktivierung ist davon getrennt.</p>
            <p><strong>Unveränderliche Produktgrenze:</strong> keine Leistungs- oder Verhaltenskontrolle.</p>
            <?php if ($_['retentionExecutionProfileSetupRequired'] ?? false): ?>
                <p id="data-protection-retention-execution-profile-setup-required" class="data-protection-warning"><strong>Noch nicht eingerichtet:</strong> Es ist keine optionale lokale Profilrevision gespeichert. Dies blockiert die getrennte technische Löschaktivierung nicht.</p>
            <?php endif; ?>
            <form id="data-protection-retention-execution-profile-form">
                <fieldset>
                    <legend>Rechtsprofil</legend>
                    <label>Geschlossenes Profil
                        <select name="profileId" required>
                            <option value="employment_collective_agreement_de">Deutsche Kollektivvereinbarung</option>
                            <option value="legitimate_interest_it_security_de">Berechtigtes Interesse IT-Sicherheit</option>
                        </select>
                    </label>
                    <label>Profilrevision <input name="profileRevision" required maxlength="64"></label>
                    <label>Rechts-/Evidenzreferenz <input name="legalEvidenceReference" required maxlength="255"></label>
                    <label>Geltungsbereich <input name="scopeReference" required maxlength="255"></label>
                    <label>Kontenkategorien <input name="accountCategories" required maxlength="255"></label>
                    <label>Zweckreferenz <input name="purposeReference" required maxlength="255"></label>
                    <label>Erforderlichkeitsnachweis <input name="necessityAssessmentReference" maxlength="255"></label>
                    <label>Auswirkungs-/Gegeninteressennachweis <input name="impactAssessmentReference" maxlength="255"></label>
                    <label>Schutzmaßnahmen <input name="safeguardsReference" required maxlength="255"></label>
                    <label><input type="checkbox" name="allAccountsEmployeesConfirmed"> Alle erfassten Konten sind Beschäftigtenkonten im Geltungsbereich</label>
                    <label>Wirksam ab <input type="datetime-local" name="effectiveAt" required></label>
                    <label>Nächste Rechtsprofilprüfung <input type="datetime-local" name="legalReviewDueAt" required></label>
                </fieldset>
                <fieldset>
                    <legend>Backup- und Restore-Nachweis</legend>
                    <label>Reguläre Aufbewahrungstage <input type="number" name="backupRegularDays" min="1" max="365" required></label>
                    <label>Technischer Puffer in Tagen <input type="number" name="backupBufferDays" min="0" max="5" required></label>
                    <label>Verantwortliche Betriebsstelle <input name="backupResponsibleParty" required maxlength="255"></label>
                    <label>Backup-System/Scope <input name="backupScope" required maxlength="255"></label>
                    <label>Backup-Evidenzreferenz <input name="backupEvidenceReference" required maxlength="255"></label>
                    <label>Backup-Nachweiszeitpunkt <input type="datetime-local" name="backupEvidenceAt" required></label>
                    <label>Nächste Backupprüfung <input type="datetime-local" name="backupReviewDueAt" required></label>
                    <label>Restore-Testreferenz <input name="restoreTestReference" required maxlength="255"></label>
                    <label>Restore-Testzeitpunkt <input type="datetime-local" name="restoreTestedAt" required></label>
                    <label><input type="checkbox" name="dpoConfirmed" required> Vollständigkeit und Nachweise durch eine berechtigte Datenschutzrolle bestätigt</label>
                </fieldset>
                <input type="hidden" name="expectedRevision" value="0">
                <button type="submit">Als neue, appseitig unveränderliche Profilrevision speichern</button>
            </form>
            <p id="data-protection-retention-execution-profile-status" role="status" aria-live="polite"><?php if ($_['retentionExecutionProfileSetupRequired'] ?? false): ?>Optionale lokale Dokumentation noch nicht eingerichtet.<?php endif; ?></p>
        </section>
    <?php endif; ?>

    <?php if ($_['canConfigureRiskScopeAuthorizations'] ?? false): ?>
        <section id="data-protection-risk-scope-authorizations" class="data-protection-report data-protection-area-card" aria-labelledby="data-protection-risk-scope-authorizations-heading">
            <h3 id="data-protection-risk-scope-authorizations-heading">Freigabe risikoreicher Funktionen</h3>
            <p>Normale Fachfunktionen bleiben unabhängig davon verfügbar. Diese Konfiguration schaltet ausschließlich die hier aufgeführte Risikofunktion kundenlokal frei. Ohne gültige Freigabe bleibt sie gesperrt.</p>
            <p><strong>Unveränderliche Produktgrenze:</strong> keine Leistungs- oder Verhaltenskontrolle.</p>
            <form id="data-protection-risk-scope-authorization-form">
                <label>Risikofunktion
                    <select name="scopeId" required>
                        <option value="flzroom.secretariat_foreign_booking_intervention">Raumplaner: begründete Eingriffe des Sekretariats in fremde Buchungen</option>
                    </select>
                </label>
                <label><input type="checkbox" name="enabled"> Freigabe aktivieren</label>
                <label>Policy-Revision <input name="policyRevision" required maxlength="64" pattern="[A-Za-z0-9][A-Za-z0-9._:/-]*"></label>
                <label>Rechts-/Evidenzreferenz <input name="authorizationReference" required maxlength="255" pattern="[A-Za-z0-9][A-Za-z0-9._:/-]*"></label>
                <label>Wirksam ab <input type="datetime-local" name="effectiveAt" required></label>
                <label>Gültig bis <input type="datetime-local" name="expiresAt" required></label>
                <label><input type="checkbox" name="dpoConfirmed" required> Vollständigkeit und Freigabe durch eine berechtigte Datenschutzrolle bestätigt</label>
                <input type="hidden" name="expectedRevision" value="0">
                <button type="submit">Als neue, appseitig unveränderliche Freigaberevision speichern</button>
            </form>
            <p id="data-protection-risk-scope-authorization-status" role="status" aria-live="polite">Risikofunktionsstatus wird geladen.</p>
        </section>
    <?php endif; ?>

            </div>
        </section>
    <?php endif; ?>

    <?php if ($showReview): ?>
        <section id="data-protection-review-panel" class="data-protection-area" role="tabpanel" aria-labelledby="data-protection-review-tab" <?php if ($defaultTab !== 'review'): ?>hidden<?php endif; ?>>
            <h2>Datenschutz-REVIEW</h2>
            <div class="data-protection-area-grid">
                <section id="data-protection-retention" class="data-protection-report data-protection-area-card" aria-labelledby="data-protection-retention-heading">
                    <h3 id="data-protection-retention-heading">Datenschutz-REVIEW-Kandidaten</h3>
                    <p>Die Vorschau wird aus den Fachapps abgerufen. Sie löst keine Löschung oder Anonymisierung aus.</p>
                    <div id="data-protection-retention-results" aria-live="polite">
                        <p role="status">REVIEW-Kandidaten werden geladen.</p>
                    </div>
                </section>
            </div>
        </section>
    <?php endif; ?>

    <?php if (($_['showMissingAdminGrant'] ?? false) || ($_['canManageAdminAccess'] ?? false) || ($_['canReadAdminAccessHistory'] ?? false)): ?>
    <div id="data-protection-admin-access-overlay" class="flz-shared-overlay" hidden>
        <div class="flz-shared-overlay__panel" role="dialog" aria-modal="true" aria-labelledby="data-protection-admin-access-overlay-title">
            <div class="flz-shared-overlay__head">
                <h2 id="data-protection-admin-access-overlay-title">Zeitlich begrenzter Admin-Vollzugriff</h2>
                <button type="button" id="data-protection-admin-access-overlay-close" aria-label="Schließen" title="Schließen">×</button>
            </div>
            <div class="flz-shared-overlay__content">
                <?php if ($_['showMissingAdminGrant'] ?? false): ?>
                    <div class="flz-shared-overlay__warning">
                        <p><strong>Kein fachlicher Admin-Vollzugriff.</strong></p>
                        <p>Native Nextcloud-Administration erteilt keinen fachlichen Vollzugriff. Es fehlt eine aktive app-lokale Freigabe.</p>
                        <p>Freigaben dürfen nur ausdrücklich berechtigte Datenschutzrollen erteilen oder widerrufen, höchstens für 24 Stunden.</p>
                    </div>
                <?php endif; ?>
                <?php if (($_['canManageAdminAccess'] ?? false) || ($_['canReadAdminAccessHistory'] ?? false)): ?>
                    <section id="data-protection-full-access" class="data-protection-report data-protection-area-card" aria-labelledby="data-protection-full-access-heading" data-can-manage="<?php p(($_['canManageAdminAccess'] ?? false) ? 'true' : 'false'); ?>">
                        <h3 id="data-protection-full-access-heading">Freigabesteuerung</h3>
                        <p>Das Verwaltungsrecht und das Leserecht für die Historie werden getrennt und serverseitig geprüft.</p>
                        <?php if ($_['canManageAdminAccess'] ?? false): ?>
                        <form id="data-protection-full-access-form">
                            <label>Admin-Benutzerkennung <input name="targetUid" required maxlength="64" autocomplete="off"></label>
                            <label>Dauer
                                <select name="durationMinutes" required>
                                    <option value="60">1 Stunde</option>
                                    <option value="240">4 Stunden</option>
                                    <option value="480">8 Stunden</option>
                                    <option value="1440">24 Stunden</option>
                                </select>
                            </label>
                            <label><input id="data-protection-full-access-enabled" name="enabled" type="checkbox" required> Vollzugriff für diesen Zeitraum aktivieren</label>
                            <button type="submit">Freigabe aktivieren</button>
                        </form>
                        <?php endif; ?>
                        <?php if ($_['canReadAdminAccessHistory'] ?? false): ?>
                        <div class="data-protection-table-wrapper">
                            <table class="data-protection-table">
                                <caption>Protokollierte Admin-Vollzugriffszeiträume</caption>
                                <thead><tr><th>Ziel-Admin</th><th>Freigegeben von</th><th>Von</th><th>Geplant bis</th><th>Tatsächlich bis / Status</th><th>Aktion</th></tr></thead>
                                <tbody id="data-protection-full-access-history"><tr><td colspan="6">Freigaben werden geladen.</td></tr></tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                        <p id="data-protection-full-access-status" role="status" aria-live="polite"></p>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>
