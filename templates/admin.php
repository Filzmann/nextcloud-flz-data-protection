<?php
script('filzmann_data_protection', 'admin');
style('filzmann_data_protection', 'style');
$settings = $_['settings'];
?>
<section id="data-protection-admin" class="data-protection-admin" aria-labelledby="data-protection-admin-heading">
    <h2 id="data-protection-admin-heading">Datenschutz-REVIEW</h2>
    <p class="data-protection-warning">
        Nextcloud-Admins dürfen REVIEW-Kandidaten nach der Neuinstallation standardmäßig lesen, damit die Funktion zuverlässig geprüft und eingerichtet werden kann.
        Prüfen Sie anschließend, ob dieses fachliche Leserecht bei Admins verbleiben soll, oder übertragen Sie es auf eine dedizierte Datenschutz-Prüfgruppe.
    </p>
    <form id="data-protection-admin-form">
        <label>
            Datenschutz-Prüfgruppen
            <textarea name="reviewer_groups" rows="4" aria-describedby="data-protection-reviewer-help"><?php p(implode("\n", $settings['reviewer_groups'])); ?></textarea>
        </label>
        <p id="data-protection-reviewer-help">Standardmäßig ist die Gruppe Datenschutzbeauftragte vorgesehen. Weitere Gruppen werden nur nach ausdrücklicher Konfiguration berechtigt.</p>
        <label>
            <input type="checkbox" name="allow_nextcloud_admin_review" value="1" <?php if ($settings['allow_nextcloud_admin_review']) { print_unescaped('checked'); } ?>>
            Nextcloud-Admins dürfen REVIEW-Kandidaten lesen
        </label>
        <button type="submit">Speichern</button>
    </form>
    <p id="data-protection-admin-notice" role="status" aria-live="polite" hidden></p>
</section>
