const translate = (typeof window !== 'undefined' && typeof window.t === 'function') ? window.t : (_app, text, params) => Object.entries(params || {}).reduce((s, [k, v]) => s.split('{' + k + '}').join(String(v)), text)
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
        if (!response.ok) throw new Error(translate('flz_data_protection', 'Retention-Prüfung ist nicht verfügbar.'));
        const continueReview = (token, sourceButton) => loadRetentionReview(token, sourceButton);
        if (continuation === null) {
            window.FlzDataProtection.retentionView.render(results, report, continueReview);
        } else {
            window.FlzDataProtection.retentionView.append(results, report, continueReview);
        }
    } catch (error) {
        const message = document.createElement('p');
        message.setAttribute('role', 'alert');
        message.textContent = translate('flz_data_protection', 'Die REVIEW-Vorschau konnte nicht geladen werden.');
        if (continuation === null) results.replaceChildren(message);
        else results.append(message);
        if (button) button.disabled = false;
    }
};

const initializeTabs = () => {
    const tabs = Array.from(document.querySelectorAll('.data-protection-tablist [role="tab"]'));
    if (tabs.length === 0) return;

    const activate = (activeTab, moveFocus = false) => {
        tabs.forEach((tab) => {
            const selected = tab === activeTab;
            tab.setAttribute('aria-selected', selected ? 'true' : 'false');
            tab.tabIndex = selected ? 0 : -1;
            const panelId = tab.getAttribute('aria-controls');
            const panel = panelId ? document.getElementById(panelId) : null;
            if (panel) panel.hidden = !selected;
        });
        if (moveFocus) activeTab.focus();
    };

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activate(tab));
        tab.addEventListener('keydown', (event) => {
            let targetIndex = null;
            if (event.key === 'ArrowRight') targetIndex = (index + 1) % tabs.length;
            if (event.key === 'ArrowLeft') targetIndex = (index - 1 + tabs.length) % tabs.length;
            if (event.key === 'Home') targetIndex = 0;
            if (event.key === 'End') targetIndex = tabs.length - 1;
            if (targetIndex === null) return;
            event.preventDefault();
            activate(tabs[targetIndex], true);
        });
    });
};

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('data-protection-app');
    if (root) {
        initializeTabs();
        loadRetentionReview();
        root.dataset.ready = 'true';
    }
});
