<?php
script('filzmann_data_protection', ['report-view', 'retention-view', 'admin-access', 'retention-policy', 'retention-execution-profile', 'main']);
style('filzmann_data_protection', 'style');
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
            <h2 id="data-protection-retention-execution-profile-heading">Deutsches Rechts- und Backup-Profil</h2>
            <p>Die Konfiguration dokumentiert ausschließlich die kundeneigenen Freigabegates. Retention bleibt technisch bei <strong>REVIEW</strong>; es wird weder ein Löschlauf noch ein Hintergrundjob aktiviert.</p>
            <p><strong>Unveränderliche Produktgrenze:</strong> keine Leistungs- oder Verhaltenskontrolle.</p>
            <?php if ($_['retentionExecutionProfileSetupRequired'] ?? false): ?>
                <p id="data-protection-retention-execution-profile-setup-required" class="data-protection-warning"><strong>Ersteinrichtung erforderlich:</strong> Erst eine vollständig gespeicherte DPO-Profilrevision dokumentiert Rechtsgrundlage, Backupgrenze und Restore-Test. Bis dahin bleibt ausschließlich REVIEW verfügbar.</p>
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
                    <label>Reguläre Aufbewahrungstage <input type="number" name="backupRegularDays" min="1" max="30" required></label>
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
            <p id="data-protection-retention-execution-profile-status" role="status" aria-live="polite"><?php if ($_['retentionExecutionProfileSetupRequired'] ?? false): ?>Ersteinrichtung erforderlich; es wurde noch keine Profilrevision gespeichert.<?php endif; ?></p>
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
