const loadSelfServiceReport = async (root) => {
    const results = document.getElementById('data-protection-results');
    try {
        const response = await fetch(OC.generateUrl('/apps/filzmann_data_protection/api/v1/self-service-report'), {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                requesttoken: OC.requestToken,
            },
        });
        const report = await response.json();
        if (!response.ok) {
            throw new Error('Self-service report unavailable.');
        }
        window.FilzmannDataProtection.reportView.render(results, report);
    } catch (error) {
        const message = document.createElement('p');
        message.setAttribute('role', 'alert');
        message.textContent = 'Die persönliche Auskunft konnte nicht geladen werden.';
        results.replaceChildren(message);
    } finally {
        root.dataset.ready = 'true';
    }
};

const loadRetentionReview = async () => {
    const results = document.getElementById('data-protection-retention-results');
    if (!results) return;
    try {
        const response = await fetch(OC.generateUrl('/apps/filzmann_data_protection/api/v1/retention-review'), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', requesttoken: OC.requestToken },
        });
        const report = await response.json();
        if (!response.ok) throw new Error('Retention review unavailable.');
        window.FilzmannDataProtection.retentionView.render(results, report);
    } catch (error) {
        const message = document.createElement('p');
        message.setAttribute('role', 'alert');
        message.textContent = 'Die REVIEW-Vorschau konnte nicht geladen werden.';
        results.replaceChildren(message);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('data-protection-app');
    if (root) {
        loadSelfServiceReport(root);
        loadRetentionReview();
    }
});
