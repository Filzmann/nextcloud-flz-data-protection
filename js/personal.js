const loadPersonalDataReport = async (root) => {
    const results = document.getElementById('data-protection-results');
    try {
        const response = await fetch(OC.generateUrl('/apps/flz_data_protection/api/v1/self-service-report'), {
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
        window.FlzDataProtection.reportView.render(results, report);
    } catch (error) {
        const message = document.createElement('p');
        message.setAttribute('role', 'alert');
        message.textContent = 'Die persönliche Auskunft konnte nicht geladen werden.';
        results.replaceChildren(message);
    } finally {
        root.dataset.ready = 'true';
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('data-protection-personal');
    if (root) loadPersonalDataReport(root);
});
