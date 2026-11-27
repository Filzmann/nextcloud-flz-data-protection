<?php
script('flz_data_protection', ['report-view', 'personal']);
style('flz_data_protection', 'style');
?>

<section id="data-protection-personal" class="data-protection-personal" aria-labelledby="data-protection-personal-heading">
    <h2 id="data-protection-personal-heading"><?php p($l->t('Persönliche Auskunft nach Art. 15 DSGVO')); ?></h2>
    <p><?php p($l->t('Die Auskunft bezieht sich ausschließlich auf das aktuell angemeldete Nextcloud-Konto. Die registrierten Apps liefern ihre Angaben für diese Anfrage; das Datenschutz-Center speichert keinen Bericht.')); ?></p>
    <div id="data-protection-results" aria-live="polite">
        <p role="status"><?php p($l->t('Persönliche Auskunft wird geladen.')); ?></p>
    </div>
</section>
