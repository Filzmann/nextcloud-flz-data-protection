<?php
script('filzmann_data_protection', ['report-view', 'retention-view', 'main']);
style('filzmann_data_protection', 'style');
?>

<main id="data-protection-app" aria-labelledby="data-protection-heading">
    <header class="data-protection-header">
        <div>
            <p class="data-protection-kicker">IKT und Datenschutz</p>
            <h1 id="data-protection-heading">Datenschutz-Center</h1>
        </div>
    </header>

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
