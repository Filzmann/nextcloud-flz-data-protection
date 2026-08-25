<?php
script('filzmann_data_protection', 'admin');
style('filzmann_data_protection', 'style');
$settings = $_['settings'];
?>
<section id="data-protection-admin" class="data-protection-admin" aria-labelledby="data-protection-admin-heading">
    <h2 id="data-protection-admin-heading">Datenschutz-REVIEW</h2>
    <p class="data-protection-warning">
        Native Nextcloud-Administration erteilt kein automatisches fachliches Leserecht.
        REVIEW-Zugriff entsteht über eine Datenschutz-Prüfgruppe oder eine höchstens 24 Stunden gültige app-lokale Freigabe.
    </p>
    <section aria-labelledby="data-protection-full-access-heading">
        <h3 id="data-protection-full-access-heading">Zeitlich begrenzter Admin-Vollzugriff</h3>
        <p>Die Freigabe gilt ausschließlich für das angegebene Administrationskonto. Beginn, geplantes Ende und ein vorzeitiger Widerruf werden protokolliert.</p>
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
    <form id="data-protection-admin-form">
        <label>
            Datenschutz-Prüfgruppen
            <textarea name="reviewer_groups" rows="4" aria-describedby="data-protection-reviewer-help"><?php p(implode("\n", $settings['reviewer_groups'])); ?></textarea>
        </label>
        <p id="data-protection-reviewer-help">Standardmäßig ist die Gruppe Datenschutzbeauftragte vorgesehen. Weitere Gruppen werden nur nach ausdrücklicher Konfiguration berechtigt.</p>
        <button type="submit">Speichern</button>
    </form>
    <p id="data-protection-admin-notice" role="status" aria-live="polite" hidden></p>
</section>
