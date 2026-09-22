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
