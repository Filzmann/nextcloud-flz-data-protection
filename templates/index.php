<?php
script('flz_data_protection', ['report-view', 'retention-view', 'admin-access', 'retention-policy', 'retention-execution-profile', 'retention-execution-activation', 'risk-scope-authorization', 'main']);
style('flz_data_protection', 'style');
?>

<main id="data-protection-app" aria-labelledby="data-protection-heading">
    <header class="data-protection-header">
        <div>
            <p class="data-protection-kicker">IKT und Datenschutz</p>
            <div class="data-protection-title-row"><h1 id="data-protection-heading">Datenschutz-Center</h1><?php if ($_['showMissingAdminGrant'] ?? false): ?><details class="data-protection-admin-grant-warning"><summary aria-label="Informationen zum fehlenden fachlichen Admin-Vollzugriff"><span aria-hidden="true">⚠</span></summary><div class="data-protection-admin-grant-warning__details"><p><strong>Kein fachlicher Admin-Vollzugriff.</strong></p><p>Native Nextcloud-Administration erteilt keinen fachlichen Vollzugriff. Es fehlt eine aktive app-lokale Freigabe.</p><p>Freigaben können ausschließlich Mitglieder von Datenschutzbeauftragte erteilen oder widerrufen, höchstens für 24 Stunden.</p><?php if ($_['showAdminAccessLink'] ?? false): ?><p><a href="#data-protection-full-access" target="_blank" rel="noopener">Freigabesteuerung in neuem Tab öffnen</a></p><?php endif; ?></div></details><?php endif; ?></div>
        </div>
    </header>

    <?php if ($_['showMissingAdminGrant'] ?? false): ?>
        <section hidden class="data-protection-report data-protection-warning" aria-labelledby="data-protection-admin-access-required-heading">
            <h2 id="data-protection-admin-access-required-heading">Kein fachlicher Admin-Vollzugriff</h2>
            <p>Native Nextcloud-Administration erteilt keinen fachlichen Vollzugriff. Für den geschützten REVIEW-Bereich fehlt eine aktive app-lokale Freigabe.</p>
            <?php if ($_['showAdminAccessLink'] ?? false): ?>
                <p><a href="#data-protection-full-access">Zur app-lokalen Freigabesteuerung</a></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($_['canConfigureAdminHistoryRetention'] ?? false): ?>
        <section id="data-protection-admin-history-retention" class="data-protection-report" aria-labelledby="data-protection-admin-history-retention-heading">
            <h2 id="data-protection-admin-history-retention-heading">Aufbewahrungsprüfung der Adminfreigabehistorie</h2>
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
        <section id="data-protection-retention-execution-profile" class="data-protection-report" aria-labelledby="data-protection-retention-execution-profile-heading">
            <h2 id="data-protection-retention-execution-profile-heading">Optionale lokale Rechts- und Betriebsdokumentation</h2>
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
                    <label><input type="checkbox" name="dpoConfirmed" required> Vollständigkeit und Nachweise durch Datenschutzbeauftragte bestätigt</label>
                </fieldset>
                <input type="hidden" name="expectedRevision" value="0">
                <button type="submit">Als neue, appseitig unveränderliche Profilrevision speichern</button>
            </form>
            <p id="data-protection-retention-execution-profile-status" role="status" aria-live="polite"><?php if ($_['retentionExecutionProfileSetupRequired'] ?? false): ?>Optionale lokale Dokumentation noch nicht eingerichtet.<?php endif; ?></p>
        </section>
    <?php endif; ?>

    <?php if ($_['canConfigureRetentionExecutionActivation'] ?? false): ?>
        <section id="data-protection-retention-execution-activation" class="data-protection-report" aria-labelledby="data-protection-retention-execution-activation-heading">
            <h2 id="data-protection-retention-execution-activation-heading">Automatische Löschung technisch aktivieren</h2>
            <p>Standardmäßig ist die Ausführung deaktiviert. Bei Aktivierung gelten die empfohlenen Policies: Raumbuchungen ein Jahr nach Buchungsende, Adminfreigabehistorien sechs Monate nach tatsächlichem Ende. Rechtsgrundlagen, Betriebsvereinbarungen und lokale Beteiligung werden außerhalb des Produkts verantwortet.</p>
            <p>Technische Schutzgrenzen bleiben zwingend: Holds, unveränderte Policyversion, atomare Löschung sowie aktueller Backup- und Restore-Status.</p>
            <form id="data-protection-retention-execution-activation-form">
                <label><input type="checkbox" name="enabled"> Automatische Löschung aktivieren</label>
                <fieldset data-retention-execution-technical-health>
                    <legend>Technischer Backup- und Restore-Status</legend>
                    <label>Backup-Aufbewahrung in Tagen <input type="number" name="backupRegularDays" min="1" max="365" value="365" required></label>
                    <label>Technischer Puffer in Tagen <input type="number" name="backupBufferDays" min="0" max="5" value="5" required></label>
                    <label>Backupstatus geprüft am <input type="datetime-local" name="backupVerifiedAt" required></label>
                    <label>Restore geprüft am <input type="datetime-local" name="restoreVerifiedAt" required></label>
                    <label>Nächste technische Prüfung spätestens <input type="datetime-local" name="verificationDueAt" required></label>
                </fieldset>
                <input type="hidden" name="expectedRevision" value="0">
                <button type="submit">Technischen Status als neue Revision speichern</button>
            </form>
            <p id="data-protection-retention-execution-activation-status" role="status" aria-live="polite">Technischer Status wird geladen.</p>
        </section>
    <?php endif; ?>

    <?php if ($_['canConfigureRiskScopeAuthorizations'] ?? false): ?>
        <section id="data-protection-risk-scope-authorizations" class="data-protection-report" aria-labelledby="data-protection-risk-scope-authorizations-heading">
            <h2 id="data-protection-risk-scope-authorizations-heading">Freigabe risikoreicher Funktionen</h2>
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
                <label><input type="checkbox" name="dpoConfirmed" required> Vollständigkeit und Freigabe durch Datenschutzbeauftragte bestätigt</label>
                <input type="hidden" name="expectedRevision" value="0">
                <button type="submit">Als neue, appseitig unveränderliche Freigaberevision speichern</button>
            </form>
            <p id="data-protection-risk-scope-authorization-status" role="status" aria-live="polite">Risikofunktionsstatus wird geladen.</p>
        </section>
    <?php endif; ?>

    <?php if ($_['canManageAdminAccess'] ?? false): ?>
        <section id="data-protection-full-access" class="data-protection-report" aria-labelledby="data-protection-full-access-heading">
            <h2 id="data-protection-full-access-heading">Zeitlich begrenzter Admin-Vollzugriff</h2>
            <p>Ausschließlich Mitglieder von Datenschutzbeauftragte dürfen einem aktuellen Nextcloud-Administrationskonto für höchstens 24 Stunden fachlichen Vollzugriff erteilen oder ihn widerrufen.</p>
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
            <p id="data-protection-full-access-status" role="status" aria-live="polite"></p>
            <div class="data-protection-table-wrapper">
                <table class="data-protection-table">
                    <caption>Protokollierte Admin-Vollzugriffszeiträume</caption>
                    <thead><tr><th>Ziel-Admin</th><th>Freigegeben von</th><th>Von</th><th>Geplant bis</th><th>Tatsächlich bis / Status</th><th>Aktion</th></tr></thead>
                    <tbody id="data-protection-full-access-history"><tr><td colspan="6">Freigaben werden geladen.</td></tr></tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <section class="data-protection-report" aria-labelledby="data-protection-status-heading">
        <h2 id="data-protection-status-heading">Eigene gespeicherte Daten</h2>
        <p>Die Daten werden für diese Anfrage bei den registrierten Apps abgerufen und nicht als Bericht gespeichert.</p>
        <div id="data-protection-results" aria-live="polite">
            <p role="status">Persönliche Auskunft wird geladen.</p>
        </div>
    </section>
    <?php if ($_['canReviewRetention'] ?? false): ?>
        <section id="data-protection-retention" class="data-protection-report" aria-labelledby="data-protection-retention-heading">
            <h2 id="data-protection-retention-heading">Datenschutz-REVIEW-Kandidaten</h2>
            <p>Die Vorschau wird aus den Fachapps abgerufen. Sie löst keine Löschung oder Anonymisierung aus.</p>
            <div id="data-protection-retention-results" aria-live="polite">
                <p role="status">REVIEW-Kandidaten werden geladen.</p>
            </div>
        </section>
    <?php endif; ?>
</main>
