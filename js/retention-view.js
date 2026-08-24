(function (window, document) {
    'use strict';
    const element = (tag, text) => {
        const node = document.createElement(tag);
        if (text !== undefined) node.textContent = String(text);
        return node;
    };
    const render = (container, report) => {
        container.replaceChildren();
        const providers = Object.entries(report.providers || {});
        if (providers.length === 0) {
            container.append(element('p', 'Noch sind keine Retention-Provider registriert.'));
            return;
        }
        providers.forEach(([appId, provider]) => {
            const article = element('article');
            article.className = 'data-protection-provider';
            article.append(element('h3', provider.displayName || appId));
            article.append(element('p', provider.status === 'complete' ? 'Vorschau vollständig' : 'Vorschau nicht vollständig'));
            const list = element('ul');
            (provider.candidates || []).forEach((candidate) => {
                list.append(element('li', `${candidate.reference}: ${candidate.reviewReason} (${candidate.action})`));
            });
            if ((provider.candidates || []).length === 0) list.append(element('li', 'Keine fälligen REVIEW-Kandidaten.'));
            article.append(list);
            container.append(article);
        });
    };
    window.FilzmannDataProtection = window.FilzmannDataProtection || {};
    window.FilzmannDataProtection.retentionView = { render };
})(window, document);
