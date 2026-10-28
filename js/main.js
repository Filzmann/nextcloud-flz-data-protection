const loadRetentionReview = async (continuation = null, button = null) => {
    const results = document.getElementById('data-protection-retention-results');
    if (!results) return;
    if (button) button.disabled = true;
    try {
        const path = continuation === null
            ? '/apps/flz_data_protection/api/v1/retention-review'
            : `/apps/flz_data_protection/api/v1/retention-review?continuation=${encodeURIComponent(continuation)}`;
        const response = await fetch(OC.generateUrl(path), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', requesttoken: OC.requestToken },
        });
        const report = await response.json();
        if (!response.ok) throw new Error('Retention review unavailable.');
        const continueReview = (token, sourceButton) => loadRetentionReview(token, sourceButton);
        if (continuation === null) {
            window.FlzDataProtection.retentionView.render(results, report, continueReview);
        } else {
            window.FlzDataProtection.retentionView.append(results, report, continueReview);
        }
    } catch (error) {
        const message = document.createElement('p');
        message.setAttribute('role', 'alert');
        message.textContent = 'Die REVIEW-Vorschau konnte nicht geladen werden.';
        if (continuation === null) results.replaceChildren(message);
        else results.append(message);
        if (button) button.disabled = false;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('data-protection-app');
    if (root) {
        loadRetentionReview();
        root.dataset.ready = 'true';
    }
});
