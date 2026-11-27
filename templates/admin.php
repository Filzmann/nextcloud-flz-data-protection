<?php
script('flz_data_protection', 'retention-execution-activation');
style('flz_data_protection', 'style');
?>
<section id="data-protection-admin" class="data-protection-admin" aria-labelledby="data-protection-admin-heading">
    <h2 id="data-protection-admin-heading"><?php p($l->t('Technischer Betrieb')); ?></h2>
    <p class="data-protection-warning"><?php p($l->t('Native Nextcloud-Administration erteilt kein automatisches fachliches Leserecht. Dieser Bereich steuert ausschließlich die technische Retention-Ausführung.')); ?></p>
    <section id="data-protection-retention-execution-activation" class="data-protection-report" aria-labelledby="data-protection-retention-execution-activation-heading">
        <h3 id="data-protection-retention-execution-activation-heading"><?php p($l->t('Automatische Löschung technisch aktivieren')); ?></h3>
        <p><?php p($l->t('Standardmäßig ist die Ausführung deaktiviert. Die fachliche Datenschutzsteuerung und die Rechtematrix befinden sich im geschützten Einstellungstab der App.')); ?></p>
        <p><?php p($l->t('Technische Schutzgrenzen bleiben zwingend: Holds, unveränderte Policyversion, atomare Löschung sowie aktueller Backup- und Restore-Status.')); ?></p>
        <form id="data-protection-retention-execution-activation-form">
            <label><input type="checkbox" name="enabled"><?php p($l->t(' Automatische Löschung aktivieren')); ?></label>
            <fieldset data-retention-execution-technical-health>
                <legend><?php p($l->t('Technischer Backup- und Restore-Status')); ?></legend>
                <label><?php p($l->t('Backup-Aufbewahrung in Tagen ')); ?><input type="number" name="backupRegularDays" min="1" max="365" value="365" required></label>
                <label><?php p($l->t('Technischer Puffer in Tagen ')); ?><input type="number" name="backupBufferDays" min="0" max="5" value="5" required></label>
                <label><?php p($l->t('Backupstatus geprüft am ')); ?><input type="datetime-local" name="backupVerifiedAt" required></label>
                <label><?php p($l->t('Restore geprüft am ')); ?><input type="datetime-local" name="restoreVerifiedAt" required></label>
                <label><?php p($l->t('Nächste technische Prüfung spätestens ')); ?><input type="datetime-local" name="verificationDueAt" required></label>
            </fieldset>
            <input type="hidden" name="expectedRevision" value="0">
            <button type="submit"><?php p($l->t('Technischen Status als neue Revision speichern')); ?></button>
        </form>
        <p id="data-protection-retention-execution-activation-status" role="status" aria-live="polite"><?php p($l->t('Technischer Status wird geladen.')); ?></p>
    </section>
</section>
