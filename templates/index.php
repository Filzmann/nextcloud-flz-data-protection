<?php
script('filzmann_data_protection', ['report-view', 'retention-view', 'admin-access', 'main']);
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
